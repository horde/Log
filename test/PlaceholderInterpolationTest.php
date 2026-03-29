<?php

/**
 * Test PSR-3 placeholder interpolation with context
 *
 * Demonstrates the recommended PSR-3 pattern from UPGRADING.md:
 * - Static message with {placeholder} syntax
 * - Context arrays with matching keys
 * - Proper exception handling
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
use Horde\Log\Formatter\Psr3Formatter;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Exception;

#[CoversClass(Logger::class)]
class PlaceholderInterpolationTest extends TestCase
{
    public function testBasicPlaceholderInterpolation(): void
    {
        // Recommended PSR-3 pattern: placeholders + context
        $message = new LogMessage(
            new LogLevel(6, 'info'),
            'User {username} logged in from {ip}',
            ['username' => 'alice', 'ip' => '192.168.1.1']
        );

        $formatter = new Psr3Formatter();
        $formatted = $message->formatMessage([$formatter]);

        $this->assertEquals('User alice logged in from 192.168.1.1', $formatted);
    }

    public function testPlaceholderWithExceptionObject(): void
    {
        // Exception object + placeholders
        $exception = new RuntimeException('Connection timeout');

        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Authentication failed for {username}',
            [
                'exception' => $exception,
                'username' => 'bob',
            ]
        );

        $formatter = new Psr3Formatter();
        $formatted = $message->formatMessage([$formatter]);

        // Message interpolated
        $this->assertEquals('Authentication failed for bob', $formatted);

        // Exception preserved in context
        $context = $message->context();
        $this->assertInstanceOf(RuntimeException::class, $context['exception']);
    }

    public function testMultiplePlaceholdersWithRichContext(): void
    {
        // Multiple placeholders + rich context
        $message = new LogMessage(
            new LogLevel(6, 'info'),
            'User {username} performed {action} on {resource}',
            [
                'username' => 'charlie',
                'action' => 'delete',
                'resource' => 'document-123',
                'timestamp' => 1234567890,
                'success' => true,
            ]
        );

        $formatter = new Psr3Formatter();
        $formatted = $message->formatMessage([$formatter]);

        $this->assertEquals(
            'User charlie performed delete on document-123',
            $formatted
        );

        // Additional context preserved
        $context = $message->context();
        $this->assertEquals(1234567890, $context['timestamp']);
        $this->assertTrue($context['success']);
    }

    public function testPlaceholderNotMatchingContextKey(): void
    {
        // Placeholder without matching context key remains unchanged
        $message = new LogMessage(
            new LogLevel(6, 'info'),
            'User {username} logged in from {ip}',
            ['username' => 'dave'] // missing 'ip'
        );

        $formatter = new Psr3Formatter();
        $formatted = $message->formatMessage([$formatter]);

        $this->assertEquals('User dave logged in from {ip}', $formatted);
    }

    public function testExceptionPlaceholderNotRecommended(): void
    {
        // Exception in placeholder is NOT recommended (exception is Stringable)
        // Better to keep exception in context only
        $exception = new Exception('Test error');

        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Error: {exception}',
            ['exception' => $exception]
        );

        $formatter = new Psr3Formatter();
        $formatted = $message->formatMessage([$formatter]);

        // Exception gets interpolated (is Stringable)
        $this->assertStringContainsString('Error: Exception: Test error', $formatted);

        // But this is not recommended - use exception message in separate key instead
    }

    public function testRecommendedExceptionPattern(): void
    {
        // RECOMMENDED: exception object + message in separate key
        $exception = new RuntimeException('Connection failed');

        $message = new LogMessage(
            new LogLevel(3, 'error'),
            'Database error: {error_message}',
            [
                'exception' => $exception,
                'error_message' => $exception->getMessage(),
            ]
        );

        $formatter = new Psr3Formatter();
        $formatted = $message->formatMessage([$formatter]);

        // Message interpolated cleanly
        $this->assertEquals('Database error: Connection failed', $formatted);

        // Full exception preserved for stack trace
        $context = $message->context();
        $this->assertInstanceOf(RuntimeException::class, $context['exception']);
    }

    public function testNumericAndBooleanContext(): void
    {
        // Test type-safe context with placeholders
        $message = new LogMessage(
            new LogLevel(6, 'info'),
            'Processed {count} items in {duration}ms',
            [
                'count' => 42,
                'duration' => 1500,
                'success' => true,
                'error_count' => 0,
            ]
        );

        $formatter = new Psr3Formatter();
        $formatted = $message->formatMessage([$formatter]);

        $this->assertEquals('Processed 42 items in 1500ms', $formatted);

        // Types preserved in context
        $context = $message->context();
        $this->assertIsInt($context['count']);
        $this->assertIsInt($context['duration']);
        $this->assertIsBool($context['success']);
    }

    public function testContextOnlyPattern(): void
    {
        // Alternative pattern: short message + rich context (no placeholders)
        // Useful for machine-consumed logs
        $message = new LogMessage(
            new LogLevel(6, 'info'),
            'User authenticated',
            [
                'username' => 'eve',
                'session_id' => 'abc123',
                'jti' => 'token-xyz',
                'success' => true,
            ]
        );

        $formatter = new Psr3Formatter();
        $formatted = $message->formatMessage([$formatter]);

        // Message unchanged (no placeholders)
        $this->assertEquals('User authenticated', $formatted);

        // All context preserved for structured backends
        $context = $message->context();
        $this->assertEquals('eve', $context['username']);
        $this->assertEquals('abc123', $context['session_id']);
        $this->assertEquals('token-xyz', $context['jti']);
        $this->assertTrue($context['success']);
    }
}
