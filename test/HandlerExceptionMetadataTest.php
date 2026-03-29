<?php

/**
 * Test handler exception metadata extraction
 *
 * Verifies that handlers correctly extract exception_class when
 * exception context contains a Throwable object, and handle
 * string exceptions appropriately.
 *
 * @author     Ralf Lang <lang@b1-systems.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test;

use PHPUnit\Framework\TestCase;
use Horde\Log\Handler\SystemdJournalHandler;
use Horde\Log\Handler\SystemdJournalOptions;
use Horde\Log\LogLevel;
use Horde\Log\LogMessage;
use ReflectionMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use InvalidArgumentException;

#[CoversClass(SystemdJournalHandler::class)]
class HandlerExceptionMetadataTest extends TestCase
{
    public function testHandlerExtractsExceptionClassFromThrowable(): void
    {
        // When exception is Throwable, handler extracts EXCEPTION_CLASS
        $options = new SystemdJournalOptions();
        $handler = new SystemdJournalHandler($options);

        $exception = new RuntimeException('Connection failed', 500);
        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Database error',
            ['exception' => $exception]
        );

        $message->formatMessage([]);

        $reflection = new ReflectionMethod($handler, 'buildPayload');
        $reflection->setAccessible(true);
        $payload = $reflection->invoke($handler, $message);

        // Handler automatically extracts exception metadata
        $this->assertStringContainsString('EXCEPTION_CLASS=RuntimeException', $payload);
        $this->assertStringContainsString('EXCEPTION_MESSAGE=Connection failed', $payload);
        $this->assertStringContainsString('EXCEPTION_CODE=500', $payload);
        $this->assertStringContainsString('EXCEPTION_FILE=', $payload);
        $this->assertStringContainsString('EXCEPTION_LINE=', $payload);
    }

    public function testHandlerAcceptsStringException(): void
    {
        // When exception is a string, handler treats it as scalar value
        $options = new SystemdJournalOptions();
        $handler = new SystemdJournalHandler($options);

        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Error occurred',
            ['exception' => 'Something went wrong']
        );

        $message->formatMessage([]);

        $reflection = new ReflectionMethod($handler, 'buildPayload');
        $reflection->setAccessible(true);
        $payload = $reflection->invoke($handler, $message);

        // String exception is serialized as-is
        $this->assertStringContainsString('EXCEPTION=Something went wrong', $payload);

        // No EXCEPTION_CLASS extracted (not a Throwable)
        $this->assertStringNotContainsString('EXCEPTION_CLASS=', $payload);
    }

    public function testHandlerWithBothThrowableAndStringException(): void
    {
        // Demonstrate difference between Throwable and string
        $options = new SystemdJournalOptions();
        $handler = new SystemdJournalHandler($options);

        // Case 1: Throwable exception
        $exception1 = new InvalidArgumentException('Invalid input');
        $message1 = new LogMessage(
            new LogLevel(3, 'error'),
            'Validation failed',
            ['exception' => $exception1, 'user' => 'alice']
        );
        $message1->formatMessage([]);

        // Case 2: String exception
        $message2 = new LogMessage(
            new LogLevel(3, 'error'),
            'Custom error',
            ['exception' => 'Custom error message', 'user' => 'bob']
        );
        $message2->formatMessage([]);

        $reflection = new ReflectionMethod($handler, 'buildPayload');
        $reflection->setAccessible(true);

        $payload1 = $reflection->invoke($handler, $message1);
        $payload2 = $reflection->invoke($handler, $message2);

        // Payload 1: Throwable - metadata extracted
        $this->assertStringContainsString('EXCEPTION_CLASS=InvalidArgumentException', $payload1);
        $this->assertStringContainsString('EXCEPTION_MESSAGE=Invalid input', $payload1);
        $this->assertStringContainsString('USER=alice', $payload1);

        // Payload 2: String - serialized as-is
        $this->assertStringContainsString('EXCEPTION=Custom error message', $payload2);
        $this->assertStringNotContainsString('EXCEPTION_CLASS=', $payload2);
        $this->assertStringContainsString('USER=bob', $payload2);
    }

    public function testHandlerExtractsCompleteExceptionMetadata(): void
    {
        // Verify all exception fields are extracted
        $options = new SystemdJournalOptions();
        $handler = new SystemdJournalHandler($options);

        $exception = new RuntimeException('Test error', 42);
        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Error',
            ['exception' => $exception]
        );
        $message->formatMessage([]);

        $reflection = new ReflectionMethod($handler, 'buildPayload');
        $reflection->setAccessible(true);
        $payload = $reflection->invoke($handler, $message);

        // All exception metadata present
        $lines = explode("\n", $payload);
        $fields = [];
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $fields[$key] = $value;
            }
        }

        $this->assertArrayHasKey('EXCEPTION_CLASS', $fields);
        $this->assertArrayHasKey('EXCEPTION_MESSAGE', $fields);
        $this->assertArrayHasKey('EXCEPTION_CODE', $fields);
        $this->assertArrayHasKey('EXCEPTION_FILE', $fields);
        $this->assertArrayHasKey('EXCEPTION_LINE', $fields);

        $this->assertEquals('RuntimeException', $fields['EXCEPTION_CLASS']);
        $this->assertEquals('Test error', $fields['EXCEPTION_MESSAGE']);
        $this->assertEquals('42', $fields['EXCEPTION_CODE']);
        $this->assertNotEmpty($fields['EXCEPTION_FILE']);
        $this->assertNotEmpty($fields['EXCEPTION_LINE']);
    }

    public function testHandlerDoesNotSerializeExceptionObject(): void
    {
        // Verify exception object itself is NOT in payload (only metadata)
        $options = new SystemdJournalOptions();
        $handler = new SystemdJournalHandler($options);

        $exception = new RuntimeException('Test');
        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Error',
            ['exception' => $exception]
        );
        $message->formatMessage([]);

        $reflection = new ReflectionMethod($handler, 'buildPayload');
        $reflection->setAccessible(true);
        $payload = $reflection->invoke($handler, $message);

        // Should NOT contain object serialization
        $this->assertStringNotContainsString('Object', $payload);
        $this->assertStringNotContainsString('__PHP_Incomplete_Class', $payload);

        // Should contain extracted metadata
        $this->assertStringContainsString('EXCEPTION_CLASS=', $payload);
    }
}
