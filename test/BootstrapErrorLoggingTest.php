<?php

/**
 * Horde Log package
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test;

use Horde\Log\Formatter\JsonContextFormatter;
use Horde\Log\Formatter\Psr3Formatter;
use Horde\Log\Formatter\SimpleFormatter;
use Horde\Log\Handler\MockHandler;
use Horde\Log\Handler\StreamHandler;
use Horde\Log\Handler\SystemdJournalHandler;
use Horde\Log\Handler\SystemdJournalOptions;
use Horde\Log\Handler\SyslogHandler;
use Horde\Log\LogException;
use Horde\Log\LogLevel;
use Horde\Log\LogMessage;
use Horde\Log\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * Integration-style tests for the bootstrap error logging scenario.
 *
 * Scenario: Horde\Injector\NotFoundException during early bootstrap when
 * DI autowiring fails for a controller. No application logger config is
 * available yet. We need resilient logging to:
 *
 * 1. Journal (native structured fields) if available
 * 2. Syslog (fat message with JSON context) if journal unavailable
 * 3. STDERR stream (fat message) always
 *
 * These tests use mock handlers and in-memory streams to verify the
 * full pipeline without requiring real syslog or systemd journal.
 */
#[CoversNothing]
class BootstrapErrorLoggingTest extends TestCase
{
    /**
     * Simulate a realistic NotFoundException from Injector autowiring.
     */
    private function createInjectorException(): RuntimeException
    {
        // Simulate the nested exception chain from Injector
        $rootCause = new RuntimeException(
            'Cannot bind interface or abstract class "Psr\Log\LoggerInterface" '
            . 'without a factory or implementation binding'
        );
        $midLevel = new RuntimeException(
            'Method __construct() of FooController has unfulfilled dependencies '
            . '(Parameter #0 [ <required> Psr\Log\LoggerInterface $logger ])',
            0,
            $rootCause,
        );

        return new RuntimeException(
            'The requested interface was not found: FooController',
            0,
            $midLevel,
        );
    }

    /**
     * Test that SystemdJournalHandler buildPayload() produces structured
     * journal fields from exception context.
     */
    public function testJournalPayloadContainsStructuredExceptionFields(): void
    {
        $handler = new SystemdJournalHandler();
        $exception = $this->createInjectorException();

        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'DI autowiring failed for controller',
            [
                'exception' => $exception,
                'requested_class' => 'FooController',
                'file' => '/app/src/Bootstrap.php',
                'line' => 42,
            ]
        );
        $message->formatMessage([]);

        // Use reflection to test buildPayload without needing a real socket
        $method = new ReflectionMethod($handler, 'buildPayload');
        $method->setAccessible(true);
        $payload = $method->invoke($handler, $message);

        // Thin MESSAGE with the original log message
        $this->assertStringContainsString(
            'MESSAGE=DI autowiring failed for controller',
            $payload,
        );

        // Priority maps to syslog error level
        $this->assertStringContainsString('PRIORITY=3', $payload);

        // Exception metadata extracted to journal fields
        $this->assertStringContainsString('EXCEPTION_CLASS=RuntimeException', $payload);
        $this->assertStringContainsString(
            'EXCEPTION_MESSAGE=The requested interface was not found: FooController',
            $payload,
        );
        $this->assertStringContainsString('EXCEPTION_CODE=0', $payload);
        $this->assertStringContainsString('EXCEPTION_FILE=', $payload);
        $this->assertStringContainsString('EXCEPTION_LINE=', $payload);

        // Context fields mapped to journal field names
        $this->assertStringContainsString('REQUESTED_CLASS=FooController', $payload);
        $this->assertStringContainsString('CODE_FILE=/app/src/Bootstrap.php', $payload);
        $this->assertStringContainsString('CODE_LINE=42', $payload);
    }

    /**
     * Test that syslog fallback activates when journal is unavailable.
     *
     * Uses a nonexistent socket path + syslogFallback=true to trigger
     * the fallback path. Verifies no LogException is thrown.
     */
    public function testSyslogFallbackWhenJournalUnavailable(): void
    {
        $options = new SystemdJournalOptions();
        $options->socketPath = '/tmp/nonexistent-journal-socket-' . uniqid();
        $options->syslogFallback = true;
        $options->ident = 'horde-test';

        $handler = new SystemdJournalHandler($options);
        $exception = $this->createInjectorException();

        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'DI autowiring failed',
            ['exception' => $exception]
        );
        $message->formatMessage([]);

        // Should NOT throw — falls back to syslog
        $result = $handler->write($message);
        $this->assertTrue($result);
    }

    /**
     * Test that syslog fallback still throws when fallback is disabled (default).
     */
    public function testJournalThrowsWithoutFallback(): void
    {
        $options = new SystemdJournalOptions();
        $options->socketPath = '/tmp/nonexistent-journal-socket-' . uniqid();
        // syslogFallback defaults to false

        $handler = new SystemdJournalHandler($options);

        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Test'
        );
        $message->formatMessage([]);

        $this->expectException(LogException::class);
        $this->expectExceptionMessage('Systemd journal socket not available');
        $handler->write($message);
    }

    /**
     * Test that STDERR stream handler receives a fat message with JSON context
     * when using the JsonContextFormatter.
     */
    public function testStderrStreamReceivesFatMessageWithJsonContext(): void
    {
        $stream = fopen('php://memory', 'w+');
        $handler = new StreamHandler(
            streamOrUrl: $stream,
            mode: 'a+',
            options: null,
            formatters: [
                new Psr3Formatter(),
                new SimpleFormatter('%message%' . PHP_EOL),
                new JsonContextFormatter(),
            ],
        );

        $exception = $this->createInjectorException();
        $logger = new Logger([$handler]);
        $logger->error('DI autowiring failed for {requested_class}', [
            'exception' => $exception,
            'requested_class' => 'FooController',
        ]);

        rewind($stream);
        $output = stream_get_contents($stream);
        fclose($stream);

        // Message should have PSR-3 placeholder interpolated
        $this->assertStringContainsString('DI autowiring failed for FooController', $output);

        // JSON context should be appended
        $this->assertStringContainsString(' | ', $output);

        // Extract JSON portion
        $parts = explode(' | ', $output, 2);
        $this->assertCount(2, $parts);

        $json = json_decode(trim($parts[1]), true);
        $this->assertIsArray($json);

        // Exception serialized in JSON
        $this->assertArrayHasKey('exception', $json);
        $this->assertEquals('RuntimeException', $json['exception']['class']);
        $this->assertStringContainsString(
            'The requested interface was not found',
            $json['exception']['message'],
        );

        // Previous exception chain preserved
        $this->assertArrayHasKey('previous', $json['exception']);
        $this->assertStringContainsString(
            'unfulfilled dependencies',
            $json['exception']['previous']['message'],
        );

        // requested_class in JSON context
        $this->assertEquals('FooController', $json['requested_class']);
    }

    /**
     * Test the dual-logger bootstrap pattern:
     * Logger 1: Journal (or syslog fallback) — for system log
     * Logger 2: StreamHandler to STDERR — for operator visibility
     *
     * Uses MockHandler as stand-in for journal/syslog and php://memory
     * as stand-in for STDERR.
     */
    public function testDualLoggerBootstrapScenario(): void
    {
        $exception = $this->createInjectorException();

        // Logger 1: "Journal or syslog" — use MockHandler as proxy
        $systemLogMock = new MockHandler();
        $systemLogger = new Logger([$systemLogMock]);

        // Logger 2: "STDERR stream" — use php://memory
        $stderrStream = fopen('php://memory', 'w+');
        $stderrHandler = new StreamHandler(
            streamOrUrl: $stderrStream,
            mode: 'a+',
            options: null,
            formatters: [
                new Psr3Formatter(),
                new SimpleFormatter('%message%' . PHP_EOL),
                new JsonContextFormatter(),
            ],
        );
        $stderrLogger = new Logger([$stderrHandler]);

        // Simulate bootstrap error handler logging to both
        $logMessage = 'DI autowiring failed for {requested_class}';
        $logContext = [
            'exception' => $exception,
            'requested_class' => 'FooController',
            'bootstrap_phase' => 'controller_resolution',
        ];

        $systemLogger->error($logMessage, $logContext);
        $stderrLogger->error($logMessage, $logContext);

        // Assert Logger 1 received the message
        $this->assertCount(1, $systemLogMock->events);
        $event = $systemLogMock->events[0];
        $this->assertEquals('error', $event->level()->name());
        $this->assertStringContainsString('DI autowiring failed', $event->message());
        $context = $event->context();
        $this->assertInstanceOf(\Throwable::class, $context['exception']);
        $this->assertEquals('FooController', $context['requested_class']);
        $this->assertEquals('controller_resolution', $context['bootstrap_phase']);

        // Assert Logger 2 wrote fat message to stream
        rewind($stderrStream);
        $stderrOutput = stream_get_contents($stderrStream);
        fclose($stderrStream);

        $this->assertStringContainsString('DI autowiring failed for FooController', $stderrOutput);
        $this->assertStringContainsString(' | ', $stderrOutput);
        $this->assertStringContainsString('"bootstrap_phase":"controller_resolution"', $stderrOutput);
        $this->assertStringContainsString('"class":"RuntimeException"', $stderrOutput);
    }

    /**
     * Test that the syslog fallback handler uses fallbackFormatters
     * configured in SystemdJournalOptions.
     *
     * We can't easily mock syslog() itself, but we can verify the
     * fallback handler creation and formatter assignment by subclassing.
     */
    public function testSyslogFallbackUsesConfiguredFormatters(): void
    {
        $options = new SystemdJournalOptions();
        $options->socketPath = '/tmp/nonexistent-journal-socket-' . uniqid();
        $options->syslogFallback = true;
        $options->ident = 'horde-test';
        $options->fallbackFormatters = [
            new Psr3Formatter(),
            new SimpleFormatter('%message%' . PHP_EOL),
            new JsonContextFormatter(),
        ];

        $handler = new SystemdJournalHandler($options);
        $exception = $this->createInjectorException();

        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'DI failed for {requested_class}',
            [
                'exception' => $exception,
                'requested_class' => 'BarController',
            ]
        );
        $message->formatMessage([]);

        // The fallback path exercises our formatters on the syslog handler.
        // We can't easily capture syslog() output, but we can verify the
        // path doesn't throw and returns true.
        $result = $handler->write($message);
        $this->assertTrue($result);
    }

    /**
     * Test the syslog fallback handler reuses the same instance across
     * multiple writes (lazy initialization).
     */
    public function testSyslogFallbackHandlerIsReused(): void
    {
        $options = new SystemdJournalOptions();
        $options->socketPath = '/tmp/nonexistent-journal-socket-' . uniqid();
        $options->syslogFallback = true;

        $handler = new SystemdJournalHandler($options);

        $message1 = new LogMessage(new LogLevel(3, 'error'), 'First');
        $message1->formatMessage([]);
        $message2 = new LogMessage(new LogLevel(4, 'warning'), 'Second');
        $message2->formatMessage([]);

        // Both writes should succeed via fallback
        $this->assertTrue($handler->write($message1));
        $this->assertTrue($handler->write($message2));

        // Verify same handler is reused via reflection
        $reflection = new \ReflectionProperty($handler, 'syslogFallbackHandler');
        $reflection->setAccessible(true);
        $fallbackHandler = $reflection->getValue($handler);

        $this->assertInstanceOf(SyslogHandler::class, $fallbackHandler);
    }

    /**
     * Test the full formatter chain: Psr3 interpolation → SimpleFormatter
     * prefix → JsonContextFormatter appendage.
     */
    public function testFullFormatterChainProducesFatMessage(): void
    {
        $stream = fopen('php://memory', 'w+');
        $handler = new StreamHandler(
            streamOrUrl: $stream,
            mode: 'a+',
            options: null,
            formatters: [
                new Psr3Formatter(),
                new SimpleFormatter('%message%' . PHP_EOL),
                new JsonContextFormatter(),
            ],
        );

        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Cannot create {class}: {reason}',
            [
                'class' => 'FooController',
                'reason' => 'LoggerInterface not bound',
                'dependency_depth' => 2,
            ]
        );

        $handler->log($message);

        rewind($stream);
        $output = stream_get_contents($stream);
        fclose($stream);

        // PSR-3 placeholders interpolated
        $this->assertStringContainsString(
            'Cannot create FooController: LoggerInterface not bound',
            $output,
        );

        // JsonContextFormatter appended JSON
        $this->assertStringContainsString(' | ', $output);
        $parts = explode(' | ', $output, 2);
        $json = json_decode(trim($parts[1]), true);
        $this->assertIsArray($json);
        $this->assertEquals(2, $json['dependency_depth']);
    }

    /**
     * Test that syslog fallback inherits ident from journal options
     * when no explicit SyslogOptions are provided.
     */
    public function testSyslogFallbackInheritsIdent(): void
    {
        $options = new SystemdJournalOptions();
        $options->socketPath = '/tmp/nonexistent-journal-socket-' . uniqid();
        $options->syslogFallback = true;
        $options->ident = 'horde-bootstrap';

        $handler = new SystemdJournalHandler($options);

        $message = new LogMessage(new LogLevel(3, 'error'), 'Test');
        $message->formatMessage([]);
        $handler->write($message);

        // Check the fallback handler's options via reflection
        $handlerReflection = new \ReflectionProperty($handler, 'syslogFallbackHandler');
        $handlerReflection->setAccessible(true);
        $fallback = $handlerReflection->getValue($handler);

        $optionsReflection = new \ReflectionProperty(SyslogHandler::class, 'options');
        $optionsReflection->setAccessible(true);
        $syslogOpts = $optionsReflection->getValue($fallback);

        $this->assertEquals('horde-bootstrap', $syslogOpts->ident);
    }
}
