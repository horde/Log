<?php

/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Log
 */

namespace Horde\Log\Test\Formatter;

use PHPUnit\Framework\TestCase;
use Horde_Log_Formatter_Xml;

/**
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Log
 */
class XmlTest extends TestCase
{
    public function setUp(): void
    {
        date_default_timezone_set('America/New_York');
    }

    public function testDefaultFormat()
    {
        $f = new Horde_Log_Formatter_Xml();
        $line = $f->format(array('message' => $message = 'message', 'level' => $level = 1));

        $this->assertStringContainsString($message, $line);
        $this->assertStringContainsString((string)$level, $line);
    }

    public function testXmlDeclarationIsStripped()
    {
        $f = new Horde_Log_Formatter_Xml();
        $line = $f->format(array('message' => $message = 'message', 'level' => $level = 1));

        $this->assertStringNotContainsString('<\?xml version=', $line);
    }

    public function testXmlValidates()
    {
        $f = new Horde_Log_Formatter_Xml();
        $line = $f->format(array('message' => $message = 'message', 'level' => $level = 1));

        $sxml = @simplexml_load_string($line);
        $this->assertInstanceOf('SimpleXMLElement', $sxml, 'Formatted XML is invalid');
    }
}
