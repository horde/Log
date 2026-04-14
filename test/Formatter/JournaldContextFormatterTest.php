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

use Closure;
use Horde\Log\Formatter\JournaldContextFormatter;
use Horde\Log\Formatter\Psr3Formatter;
use Horde\Log\LogLevel;
use Horde\Log\LogMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stringable;
use stdClass;

#[CoversClass(JournaldContextFormatter::class)]
class JournaldContextFormatterTest extends TestCase
{
    private LogLevel $errorLevel;
    private LogLevel $infoLevel;
    private JournaldContextFormatter $formatter;

    public function setUp(): void
    {
        $this->errorLevel = new LogLevel(3, 'error');
        $this->infoLevel = new LogLevel(6, 'info');
        $this->formatter = new JournaldContextFormatter();
    }

    public function testMessageFieldContainsFormattedMessage(): void
    {
        $message = new LogMessage($this->errorLevel, 'Something broke');
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertEquals('Something broke', $fields['MESSAGE']);
    }

    public function testLevelFieldContainsLevelName(): void
    {
        $message = new LogMessage($this->errorLevel, 'Test');
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertEquals('error', $fields['LEVEL']);
    }

    public function testLevelFieldReflectsActualLevel(): void
    {
        $message = new LogMessage($this->infoLevel, 'Test');
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertEquals('info', $fields['LEVEL']);
    }

    public function testContextKeysAreUppercased(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['user_id' => '42', 'request_path' => '/foo'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertArrayHasKey('USER_ID', $fields);
        $this->assertEquals('42', $fields['USER_ID']);
        $this->assertArrayHasKey('REQUEST_PATH', $fields);
        $this->assertEquals('/foo', $fields['REQUEST_PATH']);
    }

    public function testStringValuesArePreserved(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['component' => 'horde-mail'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertEquals('horde-mail', $fields['COMPONENT']);
    }

    public function testNumericValuesAreCastToString(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['count' => 7, 'ratio' => 3.14],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertEquals('7', $fields['COUNT']);
        $this->assertEquals('3.14', $fields['RATIO']);
    }

    public function testStringableObjectsCastToString(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'stringified-value';
            }
        };

        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['origin' => $stringable],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertEquals('stringified-value', $fields['ORIGIN']);
    }

    public function testArrayValuesAreDropped(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['tags' => ['a', 'b'], 'valid' => 'yes'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertArrayNotHasKey('TAGS', $fields);
        $this->assertArrayHasKey('VALID', $fields);
    }

    public function testResourceValuesAreDropped(): void
    {
        $stream = fopen('php://memory', 'r');

        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['handle' => $stream, 'valid' => 'yes'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertArrayNotHasKey('HANDLE', $fields);
        $this->assertArrayHasKey('VALID', $fields);

        fclose($stream);
    }

    public function testClosureValuesAreDropped(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['callback' => function () { return 'x'; }, 'valid' => 'yes'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertArrayNotHasKey('CALLBACK', $fields);
        $this->assertArrayHasKey('VALID', $fields);
    }

    public function testNonStringableObjectsAreDropped(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['obj' => new stdClass(), 'valid' => 'yes'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertArrayNotHasKey('OBJ', $fields);
        $this->assertArrayHasKey('VALID', $fields);
    }

    public function testNullValuesAreDropped(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['empty_field' => null, 'valid' => 'yes'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertArrayNotHasKey('EMPTY_FIELD', $fields);
        $this->assertArrayHasKey('VALID', $fields);
    }

    public function testBoolValuesAreDropped(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['flag' => true, 'valid' => 'yes'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertArrayNotHasKey('FLAG', $fields);
        $this->assertArrayHasKey('VALID', $fields);
    }

    public function testTimestampExcludedByDefault(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Test',
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertArrayNotHasKey('TIMESTAMP', $fields);
    }

    public function testCustomExcludeKeys(): void
    {
        $formatter = new JournaldContextFormatter(
            excludeKeys: ['timestamp', 'internal'],
        );

        $message = new LogMessage(
            $this->errorLevel,
            'Test',
            ['internal' => 'hidden', 'visible' => 'shown'],
        );
        $message->formatMessage([]);

        $output = $formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertArrayNotHasKey('INTERNAL', $fields);
        $this->assertArrayHasKey('VISIBLE', $fields);
    }

    public function testOutputEndsWithNewline(): void
    {
        $message = new LogMessage($this->errorLevel, 'Test');
        $message->formatMessage([]);

        $output = $this->formatter->format($message);

        $this->assertTrue(str_ends_with($output, "\n"));
    }

    public function testOutputIsNewlineDelimitedFields(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Hello',
            ['component' => 'test'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $lines = array_filter(explode("\n", $output), fn($l) => $l !== '');

        $this->assertCount(3, $lines); // MESSAGE, LEVEL, COMPONENT
        $this->assertStringStartsWith('MESSAGE=', $lines[0]);
        $this->assertStringStartsWith('LEVEL=', $lines[1]);
        $this->assertStringStartsWith('COMPONENT=', $lines[2]);
    }

    public function testMessageFieldAlwaysFirst(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'First',
            ['aaa' => 'value'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $lines = array_filter(explode("\n", $output), fn($l) => $l !== '');

        $this->assertStringStartsWith('MESSAGE=', $lines[0]);
    }

    public function testLevelFieldAlwaysSecond(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'First',
            ['aaa' => 'value'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $lines = array_filter(explode("\n", $output), fn($l) => $l !== '');

        $this->assertStringStartsWith('LEVEL=', $lines[1]);
    }

    public function testWorksWithPsr3FormatterPrepended(): void
    {
        $message = new LogMessage(
            $this->infoLevel,
            'User {user} logged in from {ip}',
            ['user' => 'alice', 'ip' => '10.0.0.1'],
        );
        $message->formatMessage([new Psr3Formatter()]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertEquals('User alice logged in from 10.0.0.1', $fields['MESSAGE']);
        $this->assertEquals('alice', $fields['USER']);
        $this->assertEquals('10.0.0.1', $fields['IP']);
    }

    public function testEmptyContextOnlyProducesMessageAndLevel(): void
    {
        // LogMessage always auto-adds timestamp, which is excluded
        $message = new LogMessage($this->errorLevel, 'Bare');
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $lines = array_filter(explode("\n", $output), fn($l) => $l !== '');

        $this->assertCount(2, $lines);
        $this->assertEquals('MESSAGE=Bare', $lines[0]);
        $this->assertEquals('LEVEL=error', $lines[1]);
    }

    public function testEmptyMessagePreservesContextMessage(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            '',
            ['MESSAGE' => 'From context'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertEquals('From context', $fields['MESSAGE']);
    }

    public function testNonEmptyMessageOverridesContextMessage(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'From log call',
            ['MESSAGE' => 'From context'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        $fields = $this->parseFields($output);

        $this->assertEquals('From log call', $fields['MESSAGE']);
    }

    public function testContextMessageKeyNotDuplicated(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            '',
            ['MESSAGE' => 'From context', 'extra' => 'val'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        // MESSAGE should appear exactly once
        $count = substr_count($output, 'MESSAGE=');
        $this->assertEquals(1, $count);
    }

    public function testLowercaseContextMessageKeyAlsoExcludedFromFields(): void
    {
        $message = new LogMessage(
            $this->errorLevel,
            'Real message',
            ['message' => 'duplicate'],
        );
        $message->formatMessage([]);

        $output = $this->formatter->format($message);
        // MESSAGE= should appear exactly once (not twice from key 'message')
        $count = substr_count($output, 'MESSAGE=');
        $this->assertEquals(1, $count);
    }

    /**
     * Parse journal-format output into an associative array of fields.
     *
     * @param string $output Newline-delimited KEY=value string
     *
     * @return array<string, string> Field name => value
     */
    private function parseFields(string $output): array
    {
        $fields = [];
        foreach (explode("\n", $output) as $line) {
            if ($line === '') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos !== false) {
                $key = substr($line, 0, $pos);
                $value = substr($line, $pos + 1);
                $fields[$key] = $value;
            }
        }
        return $fields;
    }
}
