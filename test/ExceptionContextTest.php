<?php

/**
 * Test PSR-3 exception context handling
 *
 * Tests both patterns documented in UPGRADING.md:
 * 1. Full exception object in 'exception' key (PSR-3 standard)
 * 2. Exception message string in 'exception' key (legacy fallback)
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
use Horde\Log\LogLevel;
use Horde\Log\Formatter\Psr3Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use InvalidArgumentException;
use Exception;

#[CoversClass(Logger::class)]
class ExceptionContextTest extends TestCase
{
    private Logger $logger;
    private MockHandler $handler;

    public function setUp(): void
    {
        $this->handler = new MockHandler();
        $this->logger = new Logger([$this->handler]);
    }

    public function testExceptionObjectInContext(): void
    {
        // PSR-3 standard: pass full exception object
        $exception = new RuntimeException('Database connection failed', 500);

        $this->logger->error('Service failure', [
            'exception' => $exception,
            'service' => 'database',
        ]);

        $events = $this->handler->events;
        $this->assertCount(1, $events);

        $message = $events[0];
        $context = $message->context();

        $this->assertInstanceOf(RuntimeException::class, $context['exception']);
        $this->assertEquals('Database connection failed', $context['exception']->getMessage());
        $this->assertEquals(500, $context['exception']->getCode());
        $this->assertEquals('database', $context['service']);
    }

    public function testExceptionMessageStringInContext(): void
    {
        // String values are allowed in exception key for flexibility
        // Handlers will serialize string as-is (no metadata extraction)
        $exception = new RuntimeException('Auth failed');

        $this->logger->error('Authentication failed', [
            'exception' => $exception->getMessage(),  // String allowed
            'username' => 'testuser',
        ]);

        $events = $this->handler->events;
        $this->assertCount(1, $events);

        $message = $events[0];
        $context = $message->context();

        $this->assertEquals('Auth failed', $context['exception']);
        $this->assertEquals('testuser', $context['username']);
    }

    public function testExceptionObjectWithPlaceholder(): void
    {
        // Exception in context with placeholder interpolation
        $exception = new InvalidArgumentException('Invalid token format');

        $this->logger->warning('Token validation failed for {username}', [
            'exception' => $exception,
            'username' => 'alice',
        ]);

        $events = $this->handler->events;
        $this->assertCount(1, $events);

        $message = $events[0];
        $context = $message->context();

        // Message should still have placeholder (not formatted by default)
        $this->assertStringContainsString('Token validation failed', (string) $message);
        $this->assertEquals('alice', $context['username']);
        $this->assertInstanceOf(InvalidArgumentException::class, $context['exception']);
    }

    public function testExceptionObjectWithFormatter(): void
    {
        // Test that formatters can be applied to messages
        // Note: formatMessage is called by Logger before writing
        $exception = new RuntimeException('Test error');

        $this->logger->error('Error: {message}', [
            'exception' => $exception,
            'message' => $exception->getMessage(),
        ]);

        $events = $this->handler->events;
        $this->assertCount(1, $events);

        $message = $events[0];
        $context = $message->context();

        // Context should contain both exception object and message string
        $this->assertInstanceOf(RuntimeException::class, $context['exception']);
        $this->assertEquals('Test error', $context['message']);
    }

    public function testMultipleExceptionAttributes(): void
    {
        // Test rich exception context
        // Note: exception_class is NOT required by PSR-3 (added by handlers)
        $exception = new Exception('Connection timeout');

        $this->logger->error('Service failure for {username}', [
            'exception' => $exception,  // PSR-3 standard
            'username' => 'bob',
            'service' => 'ldap',
            'timeout' => 30,
        ]);

        $events = $this->handler->events;
        $this->assertCount(1, $events);

        $context = $events[0]->context();

        $this->assertInstanceOf(Exception::class, $context['exception']);
        $this->assertEquals('bob', $context['username']);
        $this->assertEquals('ldap', $context['service']);
        $this->assertEquals(30, $context['timeout']);
    }

    public function testExceptionWithStackTrace(): void
    {
        // Verify exception object preserves stack trace
        try {
            $this->throwTestException();
        } catch (Exception $e) {
            $this->logger->critical('Uncaught exception', [
                'exception' => $e,
            ]);
        }

        $events = $this->handler->events;
        $this->assertCount(1, $events);

        $context = $events[0]->context();
        $exception = $context['exception'];

        $this->assertInstanceOf(Exception::class, $exception);

        // Stack trace should be available
        $trace = $exception->getTraceAsString();
        $this->assertStringContainsString('throwTestException', $trace);
    }

    public function testNestedExceptions(): void
    {
        // Test exception with previous exception
        $previous = new RuntimeException('Original error');
        $exception = new Exception('Wrapped error', 0, $previous);

        $this->logger->error('Nested exception occurred', [
            'exception' => $exception,
        ]);

        $events = $this->handler->events;
        $this->assertCount(1, $events);

        $context = $events[0]->context();
        $logged = $context['exception'];

        $this->assertInstanceOf(Exception::class, $logged);
        $this->assertInstanceOf(RuntimeException::class, $logged->getPrevious());
        $this->assertEquals('Original error', $logged->getPrevious()->getMessage());
    }

    public function testExceptionWithoutExceptionKey(): void
    {
        // Test logging without exception key (normal context)
        $this->logger->info('User logged in', [
            'username' => 'charlie',
            'session_id' => 'abc123',
        ]);

        $events = $this->handler->events;
        $this->assertCount(1, $events);

        $context = $events[0]->context();

        $this->assertArrayNotHasKey('exception', $context);
        $this->assertEquals('charlie', $context['username']);
        $this->assertEquals('abc123', $context['session_id']);
    }

    public function testBothPatternsCombined(): void
    {
        // Both Throwable object and string are valid in exception key
        $exception1 = new RuntimeException('Error 1');
        $exception2 = new InvalidArgumentException('Error 2');

        // Recommended: Throwable object (handlers extract metadata)
        $this->logger->error('First error', [
            'exception' => $exception1,
        ]);

        // Allowed: String value (handlers serialize as-is)
        $this->logger->error('Second error', [
            'exception' => $exception2->getMessage(),
        ]);

        $events = $this->handler->events;
        $this->assertCount(2, $events);

        $context1 = $events[0]->context();
        $context2 = $events[1]->context();

        // First: exception object (handler will extract exception_class)
        $this->assertInstanceOf(RuntimeException::class, $context1['exception']);

        // Second: exception string (handler serializes as-is)
        $this->assertEquals('Error 2', $context2['exception']);
    }

    /**
     * Helper to generate exception with stack trace
     */
    private function throwTestException(): never
    {
        throw new Exception('Test exception with trace');
    }
}
