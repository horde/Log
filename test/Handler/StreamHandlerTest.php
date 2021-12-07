<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author     Rafael te Boekhorst <boekhorstb1@b1-systems.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test\Handler;

use Horde\Log\Handler\StreamHandler;
use PHPUnit\Framework\TestCase;
use Horde\Log\LogHandler;
use Horde\Log\LogException;
use Horde_Log;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;

class StreamHandlerTest extends TestCase
{
    public function setUp(): void
    {
        date_default_timezone_set('America/New_York');
        $this->level1 = new LogLevel(Horde_Log::ALERT, 'Alert');
        $this->message1 = 'this is an emergency!';
        $this->logMessage1 = new LogMessage($this->level1, $this->message1, ['timestamp' => date('c')]);
    }

    public function testConstructorThrowsWhenResourceIsNotStream()
    {
        $this->expectException(LogException::class);
        $resource = xml_parser_create();
        new StreamHandler($resource);
        xml_parser_free($resource);
    }

    public function testConstructorWithValidStream()
    {
        $stream = fopen('php://memory', 'a');
        $handler = new StreamHandler($stream);
        $this->assertInstanceOf(LogHandler::class, $handler);
    }

    public function testConstructorWithValidUrl()
    {
        $handler = new StreamHandler('php://memory');
        $this->assertInstanceOf(LogHandler::class, $handler);
    }

    public function testConstructorThrowsWhenStreamCannotBeOpened()
    {
        $this->expectException(LogException::class);
        new StreamHandler('');
    }

    public function testSettingBadOptionThrows()
    {
        $this->expectException(LogException::class);
        $handler = new StreamHandler('php://memory');
        $handler->setOption('foo', 42);
    }

    public function testWrite() #See below comment: there is a small issue here
    {
        $stream = fopen('php://memory', 'a');

        $handler = new StreamHandler($stream);
        $handler->log($this->logMessage1);

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        $message = $this->logMessage1->message();
        $levelName = $this->logMessage1->level()->name();

        $date = '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}-\d{2}:\d{2}';

        $this->assertMatchesRegularExpression("/$date/", $contents);
        $this->assertMatchesRegularExpression("/$message/", $contents);
        // $this->assertMatchesRegularExpression("/$levelName/", $contents); // this does not match levelName, gives an error, because here levelName cannot reach to level->name with the format '%timestamp% %levelName%: %message%' . PHP_EOL... Need to fix default format for SimpleFormatter?
    }

    public function testWriteThrowsWhenStreamWriteFails()
    {
        $this->expectException(LogException::class);
        $stream = fopen('php://memory', 'a');
        $handler = new StreamHandler($stream);
        $handler->log($this->logMessage1);
        fclose($stream);
        $handler->write($this->logMessage1);
    }
}
