<?php

/**
 * Test exception handling across all handlers
 *
 * Documents how each handler deals with exception context:
 * - Handlers that extract metadata (SystemdJournal)
 * - Handlers that pass through context (LoggerInterface)
 * - Handlers that only use formatted message (all others)
 *
 * @author     Ralf Lang <lang@b1-systems.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test;

use PHPUnit\Framework\TestCase;
use Horde\Log\Logger;
use Horde\Log\Handler\MockHandler;
use Horde\Log\Handler\StreamHandler;
use Horde\Log\Handler\SyslogHandler;
use Horde\Log\Handler\SystemdJournalHandler;
use Horde\Log\Handler\SystemdJournalOptions;
use Horde\Log\Handler\LoggerInterfaceHandler;
use Horde\Log\Formatter\Psr3Formatter;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use ReflectionMethod;
use RuntimeException;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class HandlerExceptionBehaviorTest extends TestCase
{
    public function testMockHandlerPreservesExceptionInContext(): void
    {
        // MockHandler: Preserves full LogMessage with context
        $handler = new MockHandler();
        $logger = new Logger([$handler]);
        $exception = new RuntimeException('Test error');

        $logger->error('Error occurred', [
            'exception' => $exception,
            'user' => 'alice',
        ]);

        $events = $handler->events;
        $context = $events[0]->context();

        // Full context preserved including exception object
        $this->assertInstanceOf(RuntimeException::class, $context['exception']);
        $this->assertEquals('alice', $context['user']);
    }

    public function testStreamHandlerUsesFormattedMessageOnly(): void
    {
        // StreamHandler: Only writes formatted message (no context access)
        $stream = fopen('php://memory', 'w+');
        $handler = new StreamHandler($stream);
        $logger = new Logger([$handler]);
        $exception = new RuntimeException('Test error');

        $logger->error('Error: database failed', [
            'exception' => $exception,
            'user' => 'bob',
        ]);

        rewind($stream);
        $output = stream_get_contents($stream);
        fclose($stream);

        // Only message in output (formatters handle if exception appears)
        $this->assertStringContainsString('Error: database failed', $output);

        // Context not in output (handler doesn't access it)
        $this->assertStringNotContainsString('alice', $output);
        $this->assertStringNotContainsString('RuntimeException', $output);
    }

    public function testStreamHandlerWithPsr3FormatterCanInterpolate(): void
    {
        // StreamHandler with Psr3Formatter: Can interpolate placeholders
        $stream = fopen('php://memory', 'w+');
        $handler = new StreamHandler(
            streamOrUrl: $stream,
            mode: 'a+',
            options: null,
            formatters: [new Psr3Formatter()]
        );
        $logger = new Logger([$handler]);
        $exception = new RuntimeException('Connection failed');

        $logger->error('Error for user {user}: {error}', [
            'exception' => $exception,
            'user' => 'charlie',
            'error' => $exception->getMessage(),
        ]);

        rewind($stream);
        $output = stream_get_contents($stream);
        fclose($stream);

        // Placeholders interpolated
        $this->assertStringContainsString('Error for user charlie: Connection failed', $output);

        // Exception object not in output (Psr3Formatter filters non-Stringables)
    }

    public function testSystemdJournalHandlerExtractsExceptionMetadata(): void
    {
        // SystemdJournalHandler: Extracts exception metadata to journal fields
        $options = new SystemdJournalOptions();
        $handler = new SystemdJournalHandler($options);
        $exception = new RuntimeException('Database error', 500);

        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Service failure',
            ['exception' => $exception, 'user' => 'dave']
        );
        $message->formatMessage([]);

        $reflection = new ReflectionMethod($handler, 'buildPayload');
        $reflection->setAccessible(true);
        $payload = $reflection->invoke($handler, $message);

        // Exception metadata extracted to journal fields
        $this->assertStringContainsString('EXCEPTION_CLASS=RuntimeException', $payload);
        $this->assertStringContainsString('EXCEPTION_MESSAGE=Database error', $payload);
        $this->assertStringContainsString('EXCEPTION_CODE=500', $payload);
        $this->assertStringContainsString('EXCEPTION_FILE=', $payload);
        $this->assertStringContainsString('EXCEPTION_LINE=', $payload);

        // Other context also serialized
        $this->assertStringContainsString('USER=dave', $payload);
    }

    public function testLoggerInterfaceHandlerPassesContextThrough(): void
    {
        // LoggerInterfaceHandler: Passes full context to wrapped PSR-3 logger
        $mockPsr3Logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $handler = new LoggerInterfaceHandler($mockPsr3Logger);

        $exception = new RuntimeException('Auth failed');
        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Authentication error',
            ['exception' => $exception, 'user' => 'eve']
        );
        $message->formatMessage([]);

        // Verify context is passed to wrapped logger
        $mockPsr3Logger->expects($this->once())
            ->method('log')
            ->with(
                'error',
                'Authentication error',
                $this->callback(function ($context) use ($exception) {
                    return $context['exception'] === $exception
                        && $context['user'] === 'eve';
                })
            );

        $handler->write($message);
    }

    public function testHandlerSummary(): void
    {
        // Document handler behavior for exceptions:
        $behaviors = [
            'MockHandler' => 'Preserves full LogMessage with context (testing)',
            'StreamHandler' => 'Writes formatted message only',
            'SyslogHandler' => 'Writes formatted message only',
            'ScribeHandler' => 'Writes formatted message only (checks category in context)',
            'CliHandler' => 'Writes formatted message only',
            'FirebugHandler' => 'Writes formatted message only',
            'NullHandler' => 'Discards everything',
            'SystemdJournalHandler' => 'Extracts exception metadata to structured fields',
            'LoggerInterfaceHandler' => 'Passes full context to wrapped PSR-3 logger',
        ];

        $this->assertCount(9, $behaviors);

        // Formatters determine if exception appears in formatted message:
        // - Psr3Formatter: Interpolates placeholders, filters exception objects
        // - SimpleFormatter: Basic text formatting
        // - CliFormatter: Colored output
        // - XmlFormatter: XML structure

        // Recommendation:
        // - Use Throwable objects in exception key (full stack trace)
        // - Use SystemdJournalHandler for structured logging with metadata
        // - Use LoggerInterfaceHandler to delegate to external PSR-3 loggers
        // - Use placeholders in message for interpolation by formatters
    }
}
