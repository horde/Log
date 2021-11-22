<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test\Filter;

use PHPUnit\Framework\TestCase;
use Horde\Log\Filter\MaximumLevelFilter;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use TypeError;

class MaximumLevelFilterTest extends TestCase
{
    public function setUp(): void
    {
        $this->filter = new MaximumLevelFilter(2);
    }

    public function testLevelFilterAccept()
    {
        $level1 = new LogLevel(1, 'testName1');
        $level2 = new LogLevel(2, 'testName2');
        $message1 = 'testMessage1';
        $message2 = 'testMessage2';
        $logMessage1 = new LogMessage($level1, $message1);
        $logMessage2 = new LogMessage($level2, $message2);
        $this->assertTrue($this->filter->accept($logMessage1));
        $this->assertTrue($this->filter->accept($logMessage2));
    }

    public function testLevelFilterReject()
    {
        $level = new LogLevel(5, 'testName2');
        $logMessage = new LogMessage($level, "");
        $this->assertFalse($this->filter->accept($logMessage));
    }

    public function testConstructorThrowsOnInvalidLevel()
    {
        $this->expectException(TypeError::class);
        new MaximumLevelFilter('testName2');
    }
}
