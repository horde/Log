<?php

/**
 * Test PSR-3 exception key handling compliance
 *
 * Per PSR-3 specification:
 * - 'exception' key MUST contain a Throwable object
 * - Handlers should extract stack trace from exception object
 * - 'exception_class' is NOT part of PSR-3 spec (handler-specific metadata)
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
use Horde\Log\Handler\SystemdJournalHandler;
use Horde\Log\Handler\SystemdJournalOptions;
use Horde\Log\LogLevel;
use Horde\Log\LogMessage;
use ReflectionMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use InvalidArgumentException;
use Exception;
use Throwable;

#[CoversClass(SystemdJournalHandler::class)]
class Psr3ExceptionKeyComplianceTest extends TestCase
{
    public function testExceptionKeyMustContainThrowable(): void
    {
        // PSR-3: 'exception' key MUST contain a Throwable
        $logger = new Logger([new MockHandler()]);
        $exception = new RuntimeException('Test error', 42);

        $logger->error('Error occurred', [
            'exception' => $exception,
        ]);

        $this->assertTrue(true); // No exception thrown = valid
    }

    public function testExceptionKeyWithNonThrowableIsAccepted(): void
    {
        // PSR-3 says implementations must be lenient with context
        // Invalid exception values should not break logging
        $logger = new Logger([new MockHandler()]);

        // String instead of Throwable - should be accepted
        $logger->error('Error occurred', [
            'exception' => 'not an exception',
        ]);

        $this->assertTrue(true); // Should not throw
    }

    public function testExceptionContextPreservedInMockHandler(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);
        $exception = new RuntimeException('Database error');

        $logger->error('Service failure', [
            'exception' => $exception,
        ]);

        $events = $handler->events;
        $context = $events[0]->context();

        // Exception object must be preserved
        $this->assertArrayHasKey('exception', $context);
        $this->assertInstanceOf(Throwable::class, $context['exception']);
        $this->assertEquals('Database error', $context['exception']->getMessage());
    }

    public function testExceptionClassKeyIsNotPsr3Standard(): void
    {
        // 'exception_class' is NOT in PSR-3 spec
        // It's handler-specific metadata automatically added by handlers
        $handler = new MockHandler();
        $logger = new Logger([$handler]);
        $exception = new RuntimeException('Test');

        // User only needs to pass the exception object
        $logger->error('Error', [
            'exception' => $exception,
        ]);

        $events = $handler->events;
        $context = $events[0]->context();

        // Only exception key present (handler will extract exception_class)
        $this->assertArrayHasKey('exception', $context);
        $this->assertInstanceOf(Throwable::class, $context['exception']);

        // User should NOT manually add exception_class
        // Handlers will extract it automatically if needed
    }

    public function testSystemdJournalHandlerShouldExtractExceptionMetadata(): void
    {
        // SystemdJournalHandler should extract exception metadata
        // because exception objects can't be serialized to journal
        $options = new SystemdJournalOptions();
        $handler = new SystemdJournalHandler($options);

        $exception = new RuntimeException('Connection failed', 500);
        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Database error',
            ['exception' => $exception, 'username' => 'alice']
        );

        // Format message first (required before buildPayload)
        $message->formatMessage([]);

        // Use reflection to call protected buildPayload
        $reflection = new ReflectionMethod($handler, 'buildPayload');
        $reflection->setAccessible(true);
        $payload = $reflection->invoke($handler, $message);

        // Payload should contain message
        $this->assertStringContainsString('MESSAGE=Database error', $payload);

        // Scalar context should be present
        $this->assertStringContainsString('USERNAME=alice', $payload);

        // Exception metadata should be extracted by handler
        $this->assertStringContainsString('EXCEPTION_CLASS=RuntimeException', $payload);
        $this->assertStringContainsString('EXCEPTION_MESSAGE=Connection failed', $payload);
        $this->assertStringContainsString('EXCEPTION_CODE=500', $payload);
        $this->assertStringContainsString('EXCEPTION_FILE=', $payload);
        $this->assertStringContainsString('EXCEPTION_LINE=', $payload);
    }

    public function testHandlerShouldExtractExceptionClassName(): void
    {
        // Handlers (not logger) should extract exception_class for searchability
        $options = new SystemdJournalOptions();
        $handler = new SystemdJournalHandler($options);

        $exception = new InvalidArgumentException('Bad input');
        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Validation failed',
            ['exception' => $exception]
        );

        // Format message first
        $message->formatMessage([]);

        $reflection = new ReflectionMethod($handler, 'buildPayload');
        $reflection->setAccessible(true);
        $payload = $reflection->invoke($handler, $message);

        // Should contain message
        $this->assertStringContainsString('MESSAGE=Validation failed', $payload);

        // Handler should extract exception metadata
        $this->assertStringContainsString('EXCEPTION_CLASS=InvalidArgumentException', $payload);
        $this->assertStringContainsString('EXCEPTION_MESSAGE=Bad input', $payload);
        $this->assertStringContainsString('EXCEPTION_CODE=0', $payload);
    }

    public function testHandlerShouldPreserveStackTrace(): void
    {
        // Handlers should be able to extract stack trace from exception
        try {
            $this->throwTestException();
        } catch (Exception $e) {
            $handler = new MockHandler();
            $logger = new Logger([$handler]);

            $logger->critical('Uncaught exception', [
                'exception' => $e,
            ]);

            $events = $handler->events;
            $context = $events[0]->context();
            $exception = $context['exception'];

            // Stack trace must be available
            $this->assertInstanceOf(Throwable::class, $exception);
            $trace = $exception->getTraceAsString();
            $this->assertStringContainsString('throwTestException', $trace);
            $this->assertStringContainsString(__FILE__, $trace);
        }
    }

    public function testNestedExceptionChainPreserved(): void
    {
        // Exception chains must be preserved per PSR-3
        $original = new RuntimeException('Original error');
        $wrapped = new Exception('Wrapped error', 0, $original);

        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $logger->error('Exception chain', [
            'exception' => $wrapped,
        ]);

        $events = $handler->events;
        $context = $events[0]->context();
        $logged = $context['exception'];

        // Full chain preserved
        $this->assertInstanceOf(Exception::class, $logged);
        $this->assertEquals('Wrapped error', $logged->getMessage());
        $this->assertInstanceOf(RuntimeException::class, $logged->getPrevious());
        $this->assertEquals('Original error', $logged->getPrevious()->getMessage());
    }

    public function testContextLeniencyWithInvalidException(): void
    {
        // PSR-3: "implementors MUST treat context data with lenience"
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        // All of these should be accepted without throwing
        $logger->error('Test 1', ['exception' => 'string instead of exception']);
        $logger->error('Test 2', ['exception' => 123]);
        $logger->error('Test 3', ['exception' => ['array' => 'value']]);
        $logger->error('Test 4', ['exception' => null]);

        $this->assertCount(4, $handler->events);
    }

    private function throwTestException(): never
    {
        throw new Exception('Test exception for stack trace');
    }
}
