<?php

/**
 * Horde Log package
 *
 *
 * @author     Laurenz Gass <gass@b1-systems.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test;

use PHPUnit\Framework\TestCase;
use Horde\Log\Formatter\Psr3Formatter;
use Horde\Log\LogLevel;
use Horde\Log\LogMessage;
use TypeError;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Psr3Formatter::class)]
class Psr3FormatterTest extends TestCase
{
    private LogLevel $messageLog;
    private LogMessage $message;
    private Psr3Formatter $formatter;

    public function SetUp(): void
    {
        $this->messageLog = new LogLevel(3, 'info');
        $this->message = new LogMessage($this->messageLog, '{datum} everything not saved, will be lost {name}');
    }

    public function testFormatterReplacesMessageWithContext()
    {
        $this->formatter = new Psr3Formatter(['name' => 'Voldemort','datum' => '1550071894']);
        $formatted = $this->message->formatMessage([$this->formatter]);
        $this->assertIsString($formatted);
        $this->assertStringContainsString('Voldemort', $formatted);
        $this->assertStringContainsString('1550071894', $formatted);

        $this->formatter = new Psr3Formatter([]);
        $formatted = $this->message->formatMessage([$this->formatter]);
        $this->assertIsString($formatted);
        $this->assertEquals('{datum} everything not saved, will be lost {name}', $formatted);
    }

    public function testFormatsInvalidContextNotAdded()
    {
        //new message so that no timestamp is present in assertEquals.
        $this->message = new LogMessage($this->messageLog, '{datum} everything not saved, will be lost {name}');
        $this->formatter = new Psr3Formatter([["name" => "Rockolding"], "Ort" => "Rockolding", "test", $this->message ]);
        $formatted = $this->message->formatMessage([$this->formatter]);
        $this->assertIsString($formatted);
        $this->assertEquals('{datum} everything not saved, will be lost {name}', $formatted);
    }

    public function testFomatterConstructerThrowsInvalidType()
    {
        $this->expectException(TypeError::class);
        new Psr3Formatter('bar');
    }

    public function testPsr3PlaceholderFormat(): void
    {
        // PSR-3 specifies {placeholder} format with no whitespace
        $message = new LogMessage($this->messageLog, 'User {user} logged in from {ip}');
        $formatter = new Psr3Formatter(['user' => 'john', 'ip' => '192.168.1.1']);
        $formatted = $message->formatMessage([$formatter]);

        $this->assertEquals('User john logged in from 192.168.1.1', $formatted);
    }

    public function testPlaceholdersWithUnderscoresAndPeriods(): void
    {
        // PSR-3 allows A-Z, a-z, 0-9, underscore, and period in placeholder names
        $message = new LogMessage($this->messageLog, '{user_id} {user.name} {item123}');
        $formatter = new Psr3Formatter([
            'user_id' => '42',
            'user.name' => 'John Doe',
            'item123' => 'widget',
        ]);
        $formatted = $message->formatMessage([$formatter]);

        $this->assertEquals('42 John Doe widget', $formatted);
    }

    public function testContextOverridesDefaults(): void
    {
        $message = new LogMessage(
            $this->messageLog,
            'Value: {key}',
            ['key' => 'from_context']
        );
        $formatter = new Psr3Formatter(['key' => 'from_default']);
        $formatted = $message->formatMessage([$formatter]);

        // Context should override defaults
        $this->assertEquals('Value: from_context', $formatted);
    }

    public function testNumericValuesInPlaceholders(): void
    {
        $message = new LogMessage($this->messageLog, 'Count: {count}, Price: {price}');
        $formatter = new Psr3Formatter(['count' => 42, 'price' => 19.99]);
        $formatted = $message->formatMessage([$formatter]);

        $this->assertEquals('Count: 42, Price: 19.99', $formatted);
    }

    public function testStringableObjectsInPlaceholders(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable_value';
            }
        };

        $message = new LogMessage($this->messageLog, 'Object: {obj}');
        $formatter = new Psr3Formatter(['obj' => $stringable]);
        $formatted = $message->formatMessage([$formatter]);

        $this->assertEquals('Object: stringable_value', $formatted);
    }

    public function testArraysNotInterpolated(): void
    {
        // Arrays should be filtered out per PSR-3 (implementors decide)
        $message = new LogMessage($this->messageLog, 'Data: {data}');
        $formatter = new Psr3Formatter(['data' => ['array', 'value']]);
        $formatted = $message->formatMessage([$formatter]);

        // Array not converted, placeholder remains
        $this->assertEquals('Data: {data}', $formatted);
    }

    public function testObjectsNotImplementingStringableIgnored(): void
    {
        $object = new \stdClass();
        $object->property = 'value';

        $message = new LogMessage($this->messageLog, 'Object: {obj}');
        $formatter = new Psr3Formatter(['obj' => $object]);
        $formatted = $message->formatMessage([$formatter]);

        // Non-Stringable objects not converted
        $this->assertEquals('Object: {obj}', $formatted);
    }

    public function testEmptyContext(): void
    {
        $message = new LogMessage($this->messageLog, 'No placeholders here');
        $formatter = new Psr3Formatter();
        $formatted = $message->formatMessage([$formatter]);

        $this->assertEquals('No placeholders here', $formatted);
    }

    public function testMixedValidAndInvalidContext(): void
    {
        $message = new LogMessage($this->messageLog, '{valid} {array} {object}');
        $formatter = new Psr3Formatter([
            'valid' => 'ok',
            'array' => [1, 2, 3],
            'object' => new \stdClass(),
        ]);
        $formatted = $message->formatMessage([$formatter]);

        // Only valid placeholder replaced
        $this->assertEquals('ok {array} {object}', $formatted);
    }

    public function testExceptionKeyHandling(): void
    {
        // PSR-3 specifies 'exception' key should contain Exception objects
        // Exception implements Stringable, so it will be interpolated
        $exception = new \Exception('Test error');
        $message = new LogMessage(
            $this->messageLog,
            'Error occurred: {exception}',
            ['exception' => $exception]
        );
        $formatter = new Psr3Formatter();
        $formatted = $message->formatMessage([$formatter]);

        // Exception is Stringable, so it gets converted to string
        $this->assertStringContainsString('Error occurred:', $formatted);
        $this->assertStringContainsString('Exception: Test error', $formatted);
    }
}
