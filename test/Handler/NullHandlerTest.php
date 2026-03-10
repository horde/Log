<?php

/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author     Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test\Handler;

use PHPUnit\Framework\TestCase;
use Horde\Log\Handler\NullHandler;
use Horde\Log\Handler\Options;
use Horde\Log\LogException;
use Horde_Log;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NullHandler::class)]
class NullHandlerTest extends TestCase
{
    private LogLevel $level1;
    private string $message1;
    private LogMessage $logMessage1;

    public function setUp(): void
    {
        $this->level1 = new LogLevel(Horde_Log::ALERT, 'Alert');
        $this->message1 = 'this is an emergency!';
        $this->logMessage1 = new LogMessage($this->level1, $this->message1, ['randomfield' => 'stuff']);
    }

    public function testConstructor(): void
    {
        $handler = new NullHandler();
        $this->assertInstanceOf(NullHandler::class, $handler);
    }

    public function testConstructorWithOptions(): void
    {
        $options = new Options();
        $handler = new NullHandler($options);
        $this->assertInstanceOf(NullHandler::class, $handler);
    }

    public function testWrite(): void
    {
        $handler = new NullHandler();
        $this->assertTrue($handler->write($this->logMessage1));
    }

    public function testWriteAlwaysReturnsTrue(): void
    {
        $handler = new NullHandler();

        // Write multiple messages, all should return true
        $this->assertTrue($handler->write($this->logMessage1));
        $this->assertTrue($handler->write($this->logMessage1));
        $this->assertTrue($handler->write($this->logMessage1));
    }

    public function testLogMethod(): void
    {
        $handler = new NullHandler();

        // log() should work via BaseHandler (doesn't throw)
        $handler->log($this->logMessage1);
        $this->assertTrue(true);
    }

    public function testSetOption(): void
    {
        $handler = new NullHandler();

        // NullHandler has SetOptionsTrait
        $result = $handler->setOption('ident', 'TEST');
        $this->assertTrue($result);
    }
}
