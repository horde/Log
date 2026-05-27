<?php

/**
 * Tests for LogMessage
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */
declare(strict_types=1);

namespace Horde\Log\Test;

use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use Horde\Log\LogFormatter;
use DateTimeImmutable;
use DateTimeInterface;
use Horde_Log;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

#[CoversClass(LogMessage::class)]
class LogMessageTest extends TestCase
{
    private LogLevel $level;

    public function setUp(): void
    {
        $this->level = new LogLevel(Horde_Log::INFO, 'info');
    }

    public function testConstructorSetsMessage(): void
    {
        $message = new LogMessage($this->level, 'test message');
        $this->assertEquals('test message', $message->message());
    }

    public function testConstructorSetsLevel(): void
    {
        $message = new LogMessage($this->level, 'test message');
        $this->assertEquals($this->level, $message->level());
    }

    public function testConstructorSetsContext(): void
    {
        $context = ['key' => 'value', 'number' => 42];
        $message = new LogMessage($this->level, 'test message', $context);

        $resultContext = $message->context();
        $this->assertEquals('value', $resultContext['key']);
        $this->assertEquals(42, $resultContext['number']);
    }

    public function testConstructorAddsTimestampIfMissing(): void
    {
        $before = new DateTimeImmutable();
        $message = new LogMessage($this->level, 'test message');
        $after = new DateTimeImmutable();

        $this->assertInstanceOf(DateTimeImmutable::class, $message->timestamp());
        $this->assertGreaterThanOrEqual($before, $message->timestamp());
        $this->assertLessThanOrEqual($after, $message->timestamp());
    }

    public function testConstructorPromotesDateTimeInterfaceTimestamp(): void
    {
        $dt = new DateTimeImmutable('2025-06-15T12:00:00+00:00');
        $message = new LogMessage($this->level, 'test message', ['timestamp' => $dt]);

        $this->assertEquals($dt, $message->timestamp());
        $this->assertArrayNotHasKey('timestamp', $message->context());
    }

    public function testConstructorIgnoresNonDateTimeInterfaceTimestamp(): void
    {
        $message = new LogMessage($this->level, 'test message', ['timestamp' => 1716825600]);

        $this->assertInstanceOf(DateTimeImmutable::class, $message->timestamp());
        $this->assertEquals(1716825600, $message->context()['timestamp']);
    }

    public function testMergeContextAddsNewKeys(): void
    {
        $message = new LogMessage($this->level, 'test message', ['key1' => 'value1']);
        $message->mergeContext(['key2' => 'value2']);

        $context = $message->context();
        $this->assertEquals('value1', $context['key1']);
        $this->assertEquals('value2', $context['key2']);
    }

    public function testMergeContextOverwritesExistingKeys(): void
    {
        $message = new LogMessage($this->level, 'test message', ['key' => 'original']);
        $message->mergeContext(['key' => 'updated']);

        $context = $message->context();
        $this->assertEquals('updated', $context['key']);
    }

    public function testFormatMessageWithNoFormatters(): void
    {
        $message = new LogMessage($this->level, 'test message');
        $formatted = $message->formatMessage([]);

        $this->assertEquals('test message', $formatted);
    }

    public function testFormatMessageWithSingleFormatter(): void
    {
        $message = new LogMessage($this->level, 'test message');

        $formatter = new class implements LogFormatter {
            public function format(LogMessage $event): string
            {
                return '[FORMATTED] ' . $event->message();
            }
        };

        $formatted = $message->formatMessage([$formatter]);
        $this->assertEquals('[FORMATTED] test message', $formatted);
    }

    public function testFormatMessageWithMultipleFormatters(): void
    {
        $message = new LogMessage($this->level, 'test message');

        $formatter1 = new class implements LogFormatter {
            public function format(LogMessage $event): string
            {
                return '[PREFIX] ' . $event->formattedMessage();
            }
        };

        $formatter2 = new class implements LogFormatter {
            public function format(LogMessage $event): string
            {
                return $event->formattedMessage() . ' [SUFFIX]';
            }
        };

        $formatted = $message->formatMessage([$formatter1, $formatter2]);
        $this->assertEquals('[PREFIX] test message [SUFFIX]', $formatted);
    }

    public function testFormattedMessageReturnsFormattedValue(): void
    {
        $message = new LogMessage($this->level, 'test message');

        $formatter = new class implements LogFormatter {
            public function format(LogMessage $event): string
            {
                return 'FORMATTED: ' . $event->message();
            }
        };

        $message->formatMessage([$formatter]);
        $this->assertEquals('FORMATTED: test message', $message->formattedMessage());
    }

    public function testToStringReturnsFormattedMessage(): void
    {
        $message = new LogMessage($this->level, 'test message');

        $formatter = new class implements LogFormatter {
            public function format(LogMessage $event): string
            {
                return 'STRINGABLE: ' . $event->message();
            }
        };

        $message->formatMessage([$formatter]);
        $this->assertEquals('STRINGABLE: test message', (string) $message);
    }

    public function testContextCanContainArrays(): void
    {
        $context = [
            'simple' => 'value',
            'array' => [1, 2, 3],
            'nested' => ['key' => 'value'],
        ];

        $message = new LogMessage($this->level, 'test message', $context);
        $resultContext = $message->context();

        $this->assertEquals([1, 2, 3], $resultContext['array']);
        $this->assertEquals(['key' => 'value'], $resultContext['nested']);
    }

    public function testContextCanContainObjects(): void
    {
        $object = new stdClass();
        $object->property = 'value';

        $message = new LogMessage($this->level, 'test message', ['object' => $object]);
        $context = $message->context();

        $this->assertInstanceOf(stdClass::class, $context['object']);
        $this->assertEquals('value', $context['object']->property);
    }

    public function testEmptyContextWorks(): void
    {
        $message = new LogMessage($this->level, 'test message', []);
        $context = $message->context();

        $this->assertCount(0, $context);
        $this->assertInstanceOf(DateTimeImmutable::class, $message->timestamp());
    }
}
