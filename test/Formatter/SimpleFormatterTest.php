<?php

/**
 * Tests for SimpleFormatter
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */
declare(strict_types=1);

namespace Horde\Log\Test\Formatter;

use Horde\Log\Formatter\SimpleFormatter;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use DateTimeImmutable;
use Horde_Log;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use InvalidArgumentException;

#[CoversClass(SimpleFormatter::class)]
class SimpleFormatterTest extends TestCase
{
    private LogLevel $level;

    public function setUp(): void
    {
        $this->level = new LogLevel(Horde_Log::INFO, 'info');
    }

    public function testConstructorWithDefaultFormat(): void
    {
        $formatter = new SimpleFormatter();
        $this->assertInstanceOf(SimpleFormatter::class, $formatter);
    }

    public function testConstructorWithCustomFormat(): void
    {
        $formatter = new SimpleFormatter('[%levelName%] %message%');
        $this->assertInstanceOf(SimpleFormatter::class, $formatter);
    }

    public function testConstructorWithArrayOptions(): void
    {
        $formatter = new SimpleFormatter(['format' => '%message%']);
        $this->assertInstanceOf(SimpleFormatter::class, $formatter);
    }

    public function testConstructorThrowsOnInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format must be a string');
        new SimpleFormatter(123);
    }

    public function testDefaultFormatOutput(): void
    {
        $formatter = new SimpleFormatter();
        $ts = new DateTimeImmutable('2026-03-10T10:00:00+00:00');
        $message = new LogMessage($this->level, 'test message', ['timestamp' => $ts]);
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('2026-03-10T10:00:00+00:00', $output);
        $this->assertStringContainsString('test message', $output);
    }

    public function testCustomFormatWithLevelName(): void
    {
        $formatter = new SimpleFormatter('[%levelName%] %message%');
        $message = new LogMessage($this->level, 'custom message');
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('[info]', $output);
        $this->assertStringContainsString('custom message', $output);
    }

    public function testFormatWithContextVariables(): void
    {
        $formatter = new SimpleFormatter('%message% - User: %user%, IP: %ip%');
        $message = new LogMessage(
            $this->level,
            'Login attempt',
            ['user' => 'john', 'ip' => '192.168.1.1']
        );
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('Login attempt', $output);
        $this->assertStringContainsString('User: john', $output);
        $this->assertStringContainsString('IP: 192.168.1.1', $output);
    }

    public function testFormatReplacesAllPlaceholders(): void
    {
        $formatter = new SimpleFormatter('%key1% %key2% %key3%');
        $message = new LogMessage(
            $this->level,
            'message',
            ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3']
        );
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertEquals('value1 value2 value3', trim($output));
    }

    public function testFormatWithMissingContextKeys(): void
    {
        $formatter = new SimpleFormatter('%message% %missing%');
        $message = new LogMessage($this->level, 'test');
        $message->formatMessage([]);

        $output = $formatter->format($message);

        // Missing keys are left as-is
        $this->assertStringContainsString('%missing%', $output);
    }

    public function testFormatIncludesTimestamp(): void
    {
        $formatter = new SimpleFormatter('%timestamp% %message%');
        $message = new LogMessage($this->level, 'test');
        $message->formatMessage([]);

        $output = $formatter->format($message);

        // Record timestamp is always ISO 8601
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $output);
    }

    public function testNumericContextTimestampDoesNotAffectFormattedDate(): void
    {
        $formatter = new SimpleFormatter('%timestamp% %message%');
        $message = new LogMessage($this->level, 'test', ['timestamp' => 1716825600]);
        $message->formatMessage([]);

        $output = $formatter->format($message);

        // The %timestamp% placeholder shows the record's DateTimeImmutable, not the raw int
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $output);
        $this->assertStringNotContainsString('1716825600', $output);
    }

    public function testFormatAllLogLevels(): void
    {
        $formatter = new SimpleFormatter('[%levelname%] %message%');

        $levels = [
            [Horde_Log::EMERG, 'emergency'],
            [Horde_Log::ALERT, 'alert'],
            [Horde_Log::CRIT, 'critical'],
            [Horde_Log::ERR, 'error'],
            [Horde_Log::WARN, 'warning'],
            [Horde_Log::NOTICE, 'notice'],
            [Horde_Log::INFO, 'info'],
            [Horde_Log::DEBUG, 'debug'],
        ];

        foreach ($levels as [$priority, $name]) {
            $level = new LogLevel($priority, $name);
            $message = new LogMessage($level, "Test $name", ['levelname' => $name]);
            $message->formatMessage([]);

            $output = $formatter->format($message);

            $this->assertStringContainsString("[$name]", $output);
            $this->assertStringContainsString("Test $name", $output);
        }
    }

    public function testFormatWithEmptyMessage(): void
    {
        $formatter = new SimpleFormatter('%message%');
        $message = new LogMessage($this->level, '');
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertEquals('', trim($output));
    }

    public function testFormatWithNumericContextValues(): void
    {
        $formatter = new SimpleFormatter('Count: %count%, Price: %price%');
        $message = new LogMessage(
            $this->level,
            'test',
            ['count' => 42, 'price' => 19.99]
        );
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('Count: 42', $output);
        $this->assertStringContainsString('Price: 19.99', $output);
    }

    public function testFormatPreservesLineBreaks(): void
    {
        $formatter = new SimpleFormatter('%message%' . PHP_EOL);
        $message = new LogMessage($this->level, 'line1');
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString(PHP_EOL, $output);
    }

    public function testFormatWithArrayOptionFormat(): void
    {
        $formatter = new SimpleFormatter(['format' => '[CUSTOM] %message%']);
        $message = new LogMessage($this->level, 'test');
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('[CUSTOM] test', $output);
    }

    public function testFormatConvertsNonStringContextValues(): void
    {
        $formatter = new SimpleFormatter('Value: %value%');
        $message = new LogMessage(
            $this->level,
            'test',
            ['value' => true] // boolean
        );
        $message->formatMessage([]);

        $output = $formatter->format($message);

        // Should convert boolean to string
        $this->assertStringContainsString('Value: 1', $output);
    }

    public function testFormatWithArrayContextValueDoesNotWarn(): void
    {
        $formatter = new SimpleFormatter('Extra: %extra%');
        $message = new LogMessage(
            $this->level,
            'test',
            ['extra' => ['foo' => 'bar']]
        );
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('Extra: {"foo":"bar"}', $output);
    }
}
