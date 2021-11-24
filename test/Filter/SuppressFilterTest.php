<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author     Rafael te Boekhorst <boekhorstb1@b1-sytstems.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */
namespace Horde\Log\Test\Filter;

use \PHPUnit\Framework\TestCase;
use Horde\Log\Filter\SuppressFilter;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;




class SuppressFilterTest extends TestCase
{
    public function setUp(): void
    {
        $this->filter = new SuppressFilter();
        $this->level1 = new LogLevel(1, 'testName1');
        $this->level2 = new LogLevel(2, 'testName2');
        $this->level3 = new LogLevel(3, 'testName3');
        $this->message1 = "test1";
        $this->message2 = "test2";
        $this->message3 = "test3";
        $this->logMessage1 = new LogMessage($this->level1, $this->message1);
        $this->logMessage2 = new LogMessage($this->level2, $this->message2);
        $this->logMessage3 = new LogMessage($this->level3, $this->message3);
    }

    
    public function testSuppressIsInitiallyOff()
    {
        $this->assertTrue($this->filter->accept($this->logMessage1));
    }


    public function testSuppressOn()
    {
       
        $this->filter->suppress(true);
        $this->assertFalse($this->filter->accept($this->logMessage1));
        $this->assertFalse($this->filter->accept($this->logMessage2));
    }

    public function testSuppressOff()
    {
        $this->filter->suppress(false);
        $this->assertTrue($this->filter->accept($this->logMessage1));
        $this->assertTrue($this->filter->accept($this->logMessage2));
    }

    public function testSuppressCanBeReset()
    {
        $this->filter->suppress(true);
        $this->assertFalse($this->filter->accept($this->logMessage1));
        $this->filter->suppress(false);
        $this->assertTrue($this->filter->accept($this->logMessage2));
        $this->filter->suppress(true);
        $this->assertFalse($this->filter->accept($this->logMessage3));
    }
}