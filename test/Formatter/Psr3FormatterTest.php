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

class Psr3FormatterTest extends TestCase
{
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
}
