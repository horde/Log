<?php

/**
 * Horde Log package
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test\Handler;

use PHPUnit\Framework\TestCase;
use Horde\Log\Handler\FirebugHandler;
use Horde\Log\Handler\FirebugOptions;
use Horde\Log\LogLevel;
use Horde\Log\LogMessage;
use Horde_Log;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FirebugHandler::class)]
class FirebugHandlerTest extends TestCase
{
    private LogLevel $level;
    private LogMessage $logMessage;

    public function setUp(): void
    {
        $this->level = new LogLevel(Horde_Log::ERR, 'Error');
        $this->logMessage = new LogMessage($this->level, 'test error message');
    }

    public function testConstructor(): void
    {
        $handler = new FirebugHandler();
        $this->assertInstanceOf(FirebugHandler::class, $handler);
    }

    public function testConstructorWithOptions(): void
    {
        $options = new FirebugOptions();
        $options->ident = 'TEST';
        $handler = new FirebugHandler($options);
        $this->assertInstanceOf(FirebugHandler::class, $handler);
    }

    public function testWriteBuffersMessage(): void
    {
        $options = new FirebugOptions();
        $options->buffering = true;
        $handler = new FirebugHandler($options);

        // Format message before writing
        $this->logMessage->formatMessage([]);

        // Buffer should not flush immediately
        ob_start();
        $result = $handler->write($this->logMessage);
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertEmpty($output); // Nothing flushed yet
    }

    public function testWriteFlushesImmediatelyWhenBufferingDisabled(): void
    {
        $this->expectOutputRegex('/console\.error.*test error message/s');

        $options = new FirebugOptions();
        $options->buffering = false;
        $handler = new FirebugHandler($options);

        $this->logMessage->formatMessage([]);
        $handler->write($this->logMessage);
    }

    public function testFlushOutputsJavaScript(): void
    {
        $options = new FirebugOptions();
        $options->buffering = true; // Enable buffering so write() doesn't auto-flush
        $handler = new FirebugHandler($options);

        $this->logMessage->formatMessage([]);
        $handler->write($this->logMessage);

        ob_start();
        $result = $handler->flush();
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertStringContainsString('<script', $output);
        $this->assertStringContainsString('console', $output);
        $this->assertStringContainsString('test error message', $output);
    }

    public function testFlushEmptyBufferReturnsTrue(): void
    {
        $handler = new FirebugHandler();

        ob_start();
        $result = $handler->flush();
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertEmpty($output);
    }

    public function testWriteWithIdent(): void
    {
        $options = new FirebugOptions();
        $options->ident = 'MY-APP';
        $options->buffering = false;
        $handler = new FirebugHandler($options);

        $this->logMessage->formatMessage([]);

        ob_start();
        $handler->write($this->logMessage);
        $output = ob_get_clean();

        $this->assertStringContainsString('MY-APP', $output);
        $this->assertStringContainsString('test error message', $output);
    }

    public function testLogLevelMappingToFirebugMethods(): void
    {
        $testCases = [
            [Horde_Log::EMERG, 'console.error'],
            [Horde_Log::ALERT, 'console.error'],
            [Horde_Log::CRIT, 'console.error'],
            [Horde_Log::ERR, 'console.error'],
            [Horde_Log::WARN, 'console.warn'],
            [Horde_Log::NOTICE, 'console.info'],
            [Horde_Log::INFO, 'console.info'],
            [Horde_Log::DEBUG, 'console.debug'],
        ];

        $options = new FirebugOptions();
        $options->buffering = false;
        $handler = new FirebugHandler($options);

        foreach ($testCases as [$priority, $expectedMethod]) {
            $level = new LogLevel($priority, 'TestLevel');
            $message = new LogMessage($level, 'test message');
            $message->formatMessage([]);

            ob_start();
            $handler->write($message);
            $output = ob_get_clean();

            $this->assertStringContainsString($expectedMethod, $output,
                "Priority $priority should map to $expectedMethod");
        }
    }

    public function testMessageEscaping(): void
    {
        $options = new FirebugOptions();
        $options->buffering = false;
        $handler = new FirebugHandler($options);

        // Test message with quotes and newlines
        $level = new LogLevel(Horde_Log::INFO, 'Info');
        $message = new LogMessage($level, "Test \"quoted\" message\nwith newline");
        $message->formatMessage([]);

        ob_start();
        $handler->write($message);
        $output = ob_get_clean();

        // Quotes should be escaped
        $this->assertStringContainsString('\\"quoted\\"', $output);
        // Newlines should be escaped
        $this->assertStringContainsString('\\n', $output);
    }

    public function testMultipleMessagesBuffered(): void
    {
        $options = new FirebugOptions();
        $options->buffering = true;
        $handler = new FirebugHandler($options);

        $message1 = new LogMessage(new LogLevel(Horde_Log::ERR, 'Error'), 'first error');
        $message2 = new LogMessage(new LogLevel(Horde_Log::WARN, 'Warning'), 'second warning');
        $message3 = new LogMessage(new LogLevel(Horde_Log::INFO, 'Info'), 'third info');

        $message1->formatMessage([]);
        $message2->formatMessage([]);
        $message3->formatMessage([]);

        $handler->write($message1);
        $handler->write($message2);
        $handler->write($message3);

        ob_start();
        $handler->flush();
        $output = ob_get_clean();

        $this->assertStringContainsString('first error', $output);
        $this->assertStringContainsString('second warning', $output);
        $this->assertStringContainsString('third info', $output);
        $this->assertStringContainsString('console.error', $output);
        $this->assertStringContainsString('console.warn', $output);
        $this->assertStringContainsString('console.info', $output);
    }

    public function testFlushClearsBuffer(): void
    {
        $options = new FirebugOptions();
        $options->buffering = true;
        $handler = new FirebugHandler($options);

        $this->logMessage->formatMessage([]);
        $handler->write($this->logMessage);

        ob_start();
        $handler->flush();
        ob_get_clean();

        // Second flush should output nothing (buffer cleared)
        ob_start();
        $handler->flush();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testUnknownLogLevelUsesDefaultMethod(): void
    {
        $options = new FirebugOptions();
        $options->buffering = false;
        $handler = new FirebugHandler($options);

        // Use an undefined priority level
        $level = new LogLevel(999, 'Unknown');
        $message = new LogMessage($level, 'unknown level message');
        $message->formatMessage([]);

        ob_start();
        $handler->write($message);
        $output = ob_get_clean();

        // Should fall back to console.log
        $this->assertStringContainsString('console.log', $output);
        $this->assertStringContainsString('unknown level message', $output);
    }
}
