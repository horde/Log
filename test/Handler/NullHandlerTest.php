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
use Horde\Log\LogException;
use Horde_Log;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;

class NullHandlerTest extends TestCase
{
    public function testWrite()
    {
        $this->level1 = new LogLevel(Horde_Log::ALERT, 'Alert');
        $this->message1 = 'this is an emergency!';
        $this->logMessage1 = new LogMessage($this->level1, $this->message1, ['randomfield' => 'stuff']);


        $handler = new NullHandler();
        $this->assertTrue($handler->write($this->logMessage1));
    }
}
