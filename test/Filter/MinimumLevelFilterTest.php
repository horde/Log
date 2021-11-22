<?php
/**
 * Horde Log package.
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com). The package is written by Mike 
 * Naberezny and Chuck Hagenbuchhis.
 * The Package got changed from Moritz Reiter.
 *
 * @author     Moritz Reiter
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test\Filter;

use PHPUnit\Framework\TestCase;
use Horde\Log\Filter\MinimumLevelFilter;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use TypeError;

class MinimumLevelFilterTest extends TestCase
{
    public function setUp(): void
    {
        $this->filter = new MinimumLevelFilter(2);
    }

    public function testLevelFilterAccept()
    {
        $level1 = new LogLevel(1, 'testName1');
        $level2 = new LogLevel(2, 'testName2');
        $message1 = 'testMessage1';
        $message2 = 'testMessage2';
        $logMessage1 = new LogMessage($level1, $message1);
        $logMessage2 = new LogMessage($level2, $message2);
        $this->assertFalse($this->filter->accept($logMessage1));
        $this->assertTrue($this->filter->accept($logMessage2));
    }
    
    public function testLevelFilterReject()
    {
        $level = new LogLevel(5, 'testName2');
        $logMessage = new LogMessage($level, "");
        $this->assertTrue($this->filter->accept($logMessage));
    }

    public function testConstructorThrowsOnInvalidLevel()
    {
        $this->expectException(TypeError::class);
        new MinimumLevelFilter('testName2');
    }
}
