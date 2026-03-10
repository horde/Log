<?php

/**
 * Tests for CliHandler
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */
declare(strict_types=1);

namespace Horde\Log\Test\Handler;

use Horde\Log\Handler\CliHandler;
use Horde\Log\Handler\Options;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use Horde_Log;
use Horde_Cli;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CliHandler::class)]
class CliHandlerTest extends TestCase
{
    private LogLevel $level;
    private LogMessage $logMessage;

    public function setUp(): void
    {
        $this->level = new LogLevel(Horde_Log::INFO, 'info');
        $this->logMessage = new LogMessage($this->level, 'test message');
        $this->logMessage->formatMessage([]);
    }

    public function testConstructorWithDefaults(): void
    {
        $handler = new CliHandler();
        $this->assertInstanceOf(CliHandler::class, $handler);
    }

    public function testConstructorWithCustomCli(): void
    {
        $cli = new Horde_Cli();
        $handler = new CliHandler(null, $cli);
        $this->assertInstanceOf(CliHandler::class, $handler);
    }

    public function testConstructorWithCustomFormatters(): void
    {
        $formatter = new class implements \Horde\Log\LogFormatter {
            public function format(\Horde\Log\LogMessage $event): string
            {
                return '[CUSTOM] ' . $event->message();
            }
        };

        $handler = new CliHandler([$formatter]);
        $this->assertInstanceOf(CliHandler::class, $handler);
    }

    public function testWriteOutputsMessage(): void
    {
        $handler = new CliHandler();
        $result = $handler->write($this->logMessage);

        // Just verify write returns true - output goes to CLI
        $this->assertTrue($result);
    }

    public function testWriteAllLogLevels(): void
    {
        $handler = new CliHandler();

        $levels = [
            Horde_Log::EMERG => 'emergency',
            Horde_Log::ALERT => 'alert',
            Horde_Log::CRIT => 'critical',
            Horde_Log::ERR => 'error',
            Horde_Log::WARN => 'warning',
            Horde_Log::NOTICE => 'notice',
            Horde_Log::INFO => 'info',
            Horde_Log::DEBUG => 'debug',
        ];

        foreach ($levels as $priority => $name) {
            $level = new LogLevel($priority, $name);
            $message = new LogMessage($level, "Test $name message");
            $message->formatMessage([]);
            $this->assertTrue($handler->write($message));
        }
    }

    public function testWriteWithCustomFormatter(): void
    {
        $formatter = new class implements \Horde\Log\LogFormatter {
            public function format(\Horde\Log\LogMessage $event): string
            {
                return '[FORMATTED] ' . $event->message();
            }
        };

        $handler = new CliHandler([$formatter]);
        $message = new LogMessage($this->level, 'custom message');
        $message->formatMessage([$formatter]);

        $result = $handler->write($message);
        $this->assertTrue($result);
    }

    public function testLogWithFilters(): void
    {
        $handler = new CliHandler();

        // Add filter that rejects everything
        $filter = new class implements \Horde\Log\LogFilter {
            public function accept(\Horde\Log\LogMessage $event): bool
            {
                return false;
            }
        };
        $handler->addFilter($filter);

        // log() should execute without error even if filter rejects
        $handler->log($this->logMessage);
        $this->assertTrue(true);
    }

    public function testLogWithAcceptingFilter(): void
    {
        $handler = new CliHandler();

        // Add filter that accepts everything
        $filter = new class implements \Horde\Log\LogFilter {
            public function accept(\Horde\Log\LogMessage $event): bool
            {
                return true;
            }
        };
        $handler->addFilter($filter);

        $handler->log($this->logMessage);
        $this->assertTrue(true);
    }

    public function testMultipleWrites(): void
    {
        $handler = new CliHandler();

        for ($i = 0; $i < 5; $i++) {
            $message = new LogMessage($this->level, "Message $i");
            $message->formatMessage([]);
            $this->assertTrue($handler->write($message));
        }
    }
}
