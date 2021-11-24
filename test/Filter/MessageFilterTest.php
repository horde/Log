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
namespace Horde\Log\Test\Filter;


use \PHPUnit\Framework\TestCase;
use Horde\Log\Filter\MessageFilter;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;




class MessageFilterTest extends TestCase
{

    public function setUp(): void
    {
        $this->level1 = new LogLevel(1, 'testName1');
        $this->level2 = new LogLevel(2, 'testName2');
        $this->message1 = "foo accept bar";
        $this->message2 = "foo reject bar";
        $this->logMessage1 = new LogMessage($this->level1, $this->message1);
        $this->logMessage2 = new LogMessage($this->level2, $this->message2);
    }

    public function testMessageFilterRecognizesInvalidRegularExpression(){
        $this->expectException('InvalidArgumentException');
        new MessageFilter('invalid regexp');
    }

    public function testMessageFilter()
    {
        $filter = new MessageFilter('/accept/');
        $this->assertTrue($filter->accept($this->logMessage1));
        $this->assertFalse($filter->accept($this->logMessage2));
    }

}