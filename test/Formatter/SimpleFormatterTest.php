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
use RuntimeException;
use Stringable;
use stdClass;

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

    public function testUnreferencedArrayContextDoesNotWarn(): void
    {
        // Regression for the imp#88 scenario: the *template* only
        // renders %message%, but the caller carries additional array
        // context (e.g. structured `exception` details). The
        // unreferenced entry must not cast-to-string, or PHP emits an
        // "Array to string conversion" warning while the log line is
        // being written.
        $formatter = new SimpleFormatter('%message%');
        $message = new LogMessage(
            $this->level,
            'Login failure',
            [
                // Deliberately non-scalar and not referenced by the
                // template. The pre-fix code path would still cast it.
                'attempt' => ['user' => 'jan', 'ip' => '10.0.0.1'],
                'extras' => (object) ['a' => 1],
            ]
        );
        $message->formatMessage([]);

        // Fail if a warning is emitted while formatting.
        set_error_handler(static function (int $errno, string $errstr): bool {
            throw new RuntimeException(
                sprintf('Unexpected PHP notice/warning during format(): [%d] %s', $errno, $errstr),
            );
        }, E_WARNING | E_NOTICE);
        try {
            $output = $formatter->format($message);
        } finally {
            restore_error_handler();
        }

        $this->assertStringContainsString('Login failure', $output);
        // Unreferenced placeholders were never in the template — they
        // do not appear in the output.
        $this->assertStringNotContainsString('attempt', $output);
        $this->assertStringNotContainsString('extras', $output);
    }

    public function testFormatWithReferencedExceptionRendersHumanReadable(): void
    {
        // The PSR-3 reserved 'exception' key is rendered via a
        // dedicated Throwable-aware path, so a template referencing
        // %exception% gets a readable summary rather than either a
        // json_encode() of the object graph or the raw
        // Throwable::__toString() (which drops the class and code).
        $formatter = new SimpleFormatter('%message%: %exception%');
        $ex = new RuntimeException('boom', 42);
        $message = new LogMessage(
            $this->level,
            'operation failed',
            ['exception' => $ex]
        );
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('operation failed:', $output);
        $this->assertStringContainsString('RuntimeException(42): boom at ', $output);
        $this->assertStringContainsString(__FILE__, $output);
    }

    public function testFormatWithReferencedExceptionKeyNotThrowableFallsBack(): void
    {
        // PSR-3 says the 'exception' key MAY still contain non-Throwable
        // values (leniency). When it does, the formatter must fall
        // through to the generic stringify path — not throw, and not
        // try to call Throwable methods on the value.
        $formatter = new SimpleFormatter('exc=%exception%');
        $message = new LogMessage(
            $this->level,
            'test',
            ['exception' => ['not' => 'a throwable']]
        );
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('exc={"not":"a throwable"}', $output);
    }

    public function testFormatWithStringableObjectContext(): void
    {
        $formatter = new SimpleFormatter('val=%val%');
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable-value';
            }
        };
        $message = new LogMessage($this->level, 'test', ['val' => $stringable]);
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('val=stringable-value', $output);
    }

    public function testFormatWithNonStringableObjectContext(): void
    {
        // A plain object without __toString() is not treated as an
        // array (per the user's design decision: JSON only for true
        // arrays; objects go through the Stringable-or-fallback path).
        // The rendering is a safe type marker, matching Symfony's
        // HttpKernel Logger convention.
        $formatter = new SimpleFormatter('val=%val%');
        $obj = new stdClass();
        $obj->foo = 'bar';
        $message = new LogMessage($this->level, 'test', ['val' => $obj]);
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('val=[object stdClass]', $output);
    }

    public function testFormatWithResourceContext(): void
    {
        $formatter = new SimpleFormatter('val=%val%');
        $resource = fopen('php://memory', 'r');
        $this->assertIsResource($resource);
        $message = new LogMessage($this->level, 'test', ['val' => $resource]);
        $message->formatMessage([]);

        $output = $formatter->format($message);
        fclose($resource);

        $this->assertMatchesRegularExpression('/val=\[resource\([^)]+\)\]/', $output);
    }

    public function testFormatWithNullContextValue(): void
    {
        $formatter = new SimpleFormatter('before[%val%]after');
        $message = new LogMessage($this->level, 'test', ['val' => null]);
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('before[]after', $output);
    }

    public function testFormatIgnoresLiteralPercentPairsWithoutPlaceholderName(): void
    {
        // A `%%` pair (or any %<non-identifier>% sequence) must be
        // left alone — the regex only matches identifiers.
        $formatter = new SimpleFormatter('100%% done: %message%');
        $message = new LogMessage($this->level, 'ok');
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('100%% done: ok', $output);
    }

    public function testFormatWithRepeatedPlaceholder(): void
    {
        // The same %name% appearing twice in the template both get
        // substituted with the same value.
        $formatter = new SimpleFormatter('%message%/%message%');
        $message = new LogMessage($this->level, 'hi');
        $message->formatMessage([]);

        $output = $formatter->format($message);

        $this->assertStringContainsString('hi/hi', $output);
    }
}
