<?php

/**
 * Horde Log package
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test\Formatter;

use PHPUnit\Framework\TestCase;
use Horde\Log\Formatter\CliFormatter;
use Horde\Log\LogLevel;
use Horde\Log\LogMessage;
use Horde_Cli;
use Horde_Log;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CliFormatter::class)]
class CliFormatterSrcTest extends TestCase
{
    private Horde_Cli $cli;

    public function setUp(): void
    {
        $this->cli = new Horde_Cli();
    }

    public function testConstructor(): void
    {
        $formatter = new CliFormatter($this->cli);
        $this->assertInstanceOf(CliFormatter::class, $formatter);
    }

    public function testFormatEmergency(): void
    {
        $formatter = new CliFormatter($this->cli);
        $level = new LogLevel(Horde_Log::EMERG, 'emergency');
        $message = new LogMessage($level, 'emergency message');

        $output = $formatter->format($message);

        $this->assertStringContainsString('emergency', $output);
        $this->assertStringContainsString('emergency message', $output);
    }

    public function testFormatAlert(): void
    {
        $formatter = new CliFormatter($this->cli);
        $level = new LogLevel(Horde_Log::ALERT, 'alert');
        $message = new LogMessage($level, 'alert message');

        $output = $formatter->format($message);

        $this->assertStringContainsString('alert', $output);
        $this->assertStringContainsString('alert message', $output);
    }

    public function testFormatCritical(): void
    {
        $formatter = new CliFormatter($this->cli);
        $level = new LogLevel(Horde_Log::CRIT, 'critical');
        $message = new LogMessage($level, 'critical message');

        $output = $formatter->format($message);

        $this->assertStringContainsString('critical', $output);
        $this->assertStringContainsString('critical message', $output);
    }

    public function testFormatError(): void
    {
        $formatter = new CliFormatter($this->cli);
        $level = new LogLevel(Horde_Log::ERR, 'error');
        $message = new LogMessage($level, 'error message');

        $output = $formatter->format($message);

        $this->assertStringContainsString('error', $output);
        $this->assertStringContainsString('error message', $output);
    }

    public function testFormatWarning(): void
    {
        $formatter = new CliFormatter($this->cli);
        $level = new LogLevel(Horde_Log::WARN, 'warning');
        $message = new LogMessage($level, 'warning message');

        $output = $formatter->format($message);

        $this->assertStringContainsString('warning', $output);
        $this->assertStringContainsString('warning message', $output);
    }

    public function testFormatNotice(): void
    {
        $formatter = new CliFormatter($this->cli);
        $level = new LogLevel(Horde_Log::NOTICE, 'notice');
        $message = new LogMessage($level, 'notice message');

        $output = $formatter->format($message);

        $this->assertStringContainsString('notice', $output);
        $this->assertStringContainsString('notice message', $output);
    }

    public function testFormatInfo(): void
    {
        $formatter = new CliFormatter($this->cli);
        $level = new LogLevel(Horde_Log::INFO, 'info');
        $message = new LogMessage($level, 'info message');

        $output = $formatter->format($message);

        $this->assertStringContainsString('info', $output);
        $this->assertStringContainsString('info message', $output);
    }

    public function testFormatDebug(): void
    {
        $formatter = new CliFormatter($this->cli);
        $level = new LogLevel(Horde_Log::DEBUG, 'debug');
        $message = new LogMessage($level, 'debug message');

        $output = $formatter->format($message);

        $this->assertStringContainsString('debug', $output);
        $this->assertStringContainsString('debug message', $output);
    }

    public function testFormatCustomLevel(): void
    {
        $formatter = new CliFormatter($this->cli);
        $level = new LogLevel(999, 'custom');
        $message = new LogMessage($level, 'custom message');

        $output = $formatter->format($message);

        $this->assertStringContainsString('custom', $output);
        $this->assertStringContainsString('custom message', $output);
    }

    public function testFormatPadsLevelName(): void
    {
        $formatter = new CliFormatter($this->cli);
        $level = new LogLevel(Horde_Log::INFO, 'info');
        $message = new LogMessage($level, 'test');

        $output = $formatter->format($message);

        // Level name should be padded to 7 characters with brackets
        $this->assertMatchesRegularExpression('/\[ *info *\]/', $output);
    }

    public function testFormatWithLongMessage(): void
    {
        $formatter = new CliFormatter($this->cli);
        $level = new LogLevel(Horde_Log::INFO, 'info');
        $longMessage = str_repeat('This is a long message. ', 10);
        $message = new LogMessage($level, $longMessage);

        $output = $formatter->format($message);

        $this->assertStringContainsString($longMessage, $output);
    }

    public function testFormatMultipleMessages(): void
    {
        $formatter = new CliFormatter($this->cli);

        $messages = [
            ['level' => Horde_Log::ERR, 'name' => 'error', 'text' => 'error 1'],
            ['level' => Horde_Log::WARN, 'name' => 'warning', 'text' => 'warning 1'],
            ['level' => Horde_Log::INFO, 'name' => 'info', 'text' => 'info 1'],
        ];

        foreach ($messages as $msgData) {
            $level = new LogLevel($msgData['level'], $msgData['name']);
            $message = new LogMessage($level, $msgData['text']);
            $output = $formatter->format($message);

            $this->assertStringContainsString($msgData['name'], $output);
            $this->assertStringContainsString($msgData['text'], $output);
        }
    }

    public function testFormatPreservesMessageContent(): void
    {
        $formatter = new CliFormatter($this->cli);
        $level = new LogLevel(Horde_Log::INFO, 'info');

        $specialMessage = 'Message with $pecial ch@racters & symbols!';
        $message = new LogMessage($level, $specialMessage);

        $output = $formatter->format($message);

        $this->assertStringContainsString($specialMessage, $output);
    }
}
