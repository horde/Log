<?php

/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author   Rafael te Boekhorst <boekhorstb1@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Log
 */

namespace Horde\Log\Test\Formatter;

use PHPUnit\Framework\TestCase;

use Horde_Cli;
use Horde\Log\Formatter\CliFormatter;

use Horde\Log\LogMessage;
use Horde\Log\LogLevel;

class CliFormatterTest extends TestCase
{
    public function setUp(): void
    {
        $this->cli = new Horde_Cli();

        $this->level1 = new LogLevel(1, 'Emergency');
        $this->level2 = new LogLevel(2, 'warning');
        $this->level3 = new LogLevel(3, 'info');
        $this->level4 = new LogLevel(4, 'Some other value');
        $this->message1 = 'this is an emergency!';
        $this->message2 = 'this is a warning!';
        $this->message3 = 'some info here!';
        $this->message4 = 'some other info here!';
        $this->logMessage1 = new LogMessage($this->level1, $this->message1);
        $this->logMessage2 = new LogMessage($this->level2, $this->message2);
        $this->logMessage3 = new LogMessage($this->level3, $this->message3);
        $this->logMessage4 = new LogMessage($this->level4, $this->message4);
    }

    public function testDefaultFormat()
    {
        $f = new CliFormatter($this->cli);
        $line = $f->format($this->logMessage1);

        $loglevel = $this->logMessage1->level();
        $name = $loglevel->name();

        # Note: the cliformatter does not output the value of "Criticallity"
        // $criticality = $loglevel->criticality();

        $this->assertStringContainsString($this->message1, $line);
        $this->assertStringContainsString($name, $line);
    }

    public function testColorSettings()
    {
        $f = new CliFormatter($this->cli);
        $logsarray = [$this->logMessage1, $this->logMessage2, $this->logMessage3, $this->logMessage4];

        foreach ($logsarray as $key => $value) {
            $line = $f->format($value);
            $loglevel = $value->level();
            $name = $loglevel->name();
            $logmessage = $value->message();
            $flag = '[' . str_pad($name, 7, ' ', STR_PAD_BOTH) . '] ';

            switch ($name) {
                case 'emergency':
                    $this->assertEquals($this->cli->color('red', $flag) . $logmessage, $line);
                    break;
                case 'warning':
                    $this->assertEquals($this->cli->color('yellow', $flag) . $logmessage, $line);
                    break;
                case 'info':
                    $this->assertEquals($this->cli->color('blue', $flag) . $logmessage, $line);
                    break;
                default:
                    $this->assertEquals($flag . $logmessage, $line);
                    break;
            }
        }
    }
}
