<?php

/**
 * Horde Log package
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test\Formatter;

use Horde\Log\Formatter\JsonContextFormatter;
use Horde\Log\LogLevel;
use Horde\Log\LogMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stringable;
use stdClass;

#[CoversClass(JsonContextFormatter::class)]
class JsonContextFormatterTest extends TestCase
{
    private LogLevel $errorLevel;
    private JsonContextFormatter $formatter;

    public function setUp(): void
    {
        $this->errorLevel = new LogLevel(3, 'error');
        $this->formatter = new JsonContextFormatter();
    }

    public function testAppendsJsonContext(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'DI autowiring failed',
            [
                'requested' => 'FooController',
                'root_cause' => 'BarInterface has no binding',
            ]
        );
        $message->formatMessage([]);

        $formatted = $this->formatter->format($message);

        $this->assertStringContainsString('DI autowiring failed', $formatted);
        $this->assertStringContainsString(' | ', $formatted);

        // Extract the JSON part
        $parts = explode(' | ', $formatted, 2);
        $json = json_decode($parts[1], true);

        $this->assertIsArray($json);
        $this->assertEquals('FooController', $json['requested']);
        $this->assertEquals('BarInterface has no binding', $json['root_cause']);
    }

    public function testOmitsTimestampByDefault(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test message',
            ['key' => 'value']
        );
        $message->formatMessage([]);

        $formatted = $this->formatter->format($message);
        $parts = explode(' | ', $formatted, 2);
        $json = json_decode($parts[1], true);

        $this->assertArrayNotHasKey('timestamp', $json);
    }

    public function testSerializesExceptionInContext(): void
    {
        $previous = new RuntimeException('Root cause', 42);
        $exception = new RuntimeException('Top level error', 100, $previous);

        $message = new LogMessage(
            $this->errorLevel,
            'Something failed',
            ['exception' => $exception]
        );
        $message->formatMessage([]);

        $formatted = $this->formatter->format($message);
        $parts = explode(' | ', $formatted, 2);
        $json = json_decode($parts[1], true);

        $this->assertArrayHasKey('exception', $json);
        $exData = $json['exception'];
        $this->assertEquals('RuntimeException', $exData['class']);
        $this->assertEquals('Top level error', $exData['message']);
        $this->assertEquals(100, $exData['code']);
        $this->assertArrayHasKey('file', $exData);
        $this->assertArrayHasKey('line', $exData);

        // Previous exception is also serialized
        $this->assertArrayHasKey('previous', $exData);
        $this->assertEquals('Root cause', $exData['previous']['message']);
        $this->assertEquals(42, $exData['previous']['code']);
    }

    public function testFiltersNonSerializableValues(): void
    {
        $stream = fopen('php://memory', 'r');
        $closure = function () {
            return 'test';
        };

        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            [
                'valid_string' => 'hello',
                'valid_int' => 42,
                'resource' => $stream,
                'closure' => $closure,
                'object' => new stdClass(),
            ]
        );
        $message->formatMessage([]);

        $formatted = $this->formatter->format($message);
        $parts = explode(' | ', $formatted, 2);
        $json = json_decode($parts[1], true);

        $this->assertArrayHasKey('valid_string', $json);
        $this->assertArrayHasKey('valid_int', $json);
        $this->assertArrayNotHasKey('resource', $json);
        $this->assertArrayNotHasKey('closure', $json);
        $this->assertArrayNotHasKey('object', $json);

        fclose($stream);
    }

    public function testStringableObjectsConvertedToString(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'I am stringable';
            }
        };

        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['obj' => $stringable]
        );
        $message->formatMessage([]);

        $formatted = $this->formatter->format($message);
        $parts = explode(' | ', $formatted, 2);
        $json = json_decode($parts[1], true);

        $this->assertEquals('I am stringable', $json['obj']);
    }

    public function testEmptyContextProducesNoJsonAppendage(): void
    {
        // Only 'timestamp' in context, which is excluded by default
        $message = new LogMessage(
            $this->errorLevel,
            'Bare message'
        );
        $message->formatMessage([]);

        $formatted = $this->formatter->format($message);

        $this->assertStringNotContainsString(' | ', $formatted);
        $this->assertEquals('Bare message', $formatted);
    }

    public function testCustomSeparator(): void
    {
        $formatter = new JsonContextFormatter(separator: ' ## ');

        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['key' => 'value']
        );
        $message->formatMessage([]);

        $formatted = $formatter->format($message);

        $this->assertStringContainsString(' ## ', $formatted);
        $this->assertStringNotContainsString(' | ', $formatted);
    }

    public function testCustomExcludeKeys(): void
    {
        $formatter = new JsonContextFormatter(
            excludeKeys: ['timestamp', 'internal_debug']
        );

        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            [
                'visible' => 'yes',
                'internal_debug' => 'should be excluded',
            ]
        );
        $message->formatMessage([]);

        $formatted = $formatter->format($message);
        $parts = explode(' | ', $formatted, 2);
        $json = json_decode($parts[1], true);

        $this->assertArrayHasKey('visible', $json);
        $this->assertArrayNotHasKey('internal_debug', $json);
        $this->assertArrayNotHasKey('timestamp', $json);
    }

    public function testPreservesTrailingNewline(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test with newline',
            ['key' => 'value']
        );
        // SimpleFormatter adds PHP_EOL
        $message->formatMessage([new \Horde\Log\Formatter\SimpleFormatter()]);

        $formatted = $this->formatter->format($message);

        // Should end with PHP_EOL
        $this->assertTrue(str_ends_with($formatted, PHP_EOL));

        // JSON should be before the newline
        $trimmed = rtrim($formatted);
        $this->assertTrue(str_ends_with($trimmed, '}'));
    }

    public function testNestedArraysAreSerialized(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            [
                'chain' => ['Foo', 'Bar', 'Baz'],
                'nested' => ['a' => 1, 'b' => ['c' => 2]],
            ]
        );
        $message->formatMessage([]);

        $formatted = $this->formatter->format($message);
        $parts = explode(' | ', $formatted, 2);
        $json = json_decode($parts[1], true);

        $this->assertEquals(['Foo', 'Bar', 'Baz'], $json['chain']);
        $this->assertEquals(['a' => 1, 'b' => ['c' => 2]], $json['nested']);
    }

    public function testNullAndBoolValuesPreserved(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            [
                'null_val' => null,
                'true_val' => true,
                'false_val' => false,
            ]
        );
        $message->formatMessage([]);

        $formatted = $this->formatter->format($message);
        $parts = explode(' | ', $formatted, 2);
        $json = json_decode($parts[1], true);

        $this->assertNull($json['null_val']);
        $this->assertTrue($json['true_val']);
        $this->assertFalse($json['false_val']);
    }
}
