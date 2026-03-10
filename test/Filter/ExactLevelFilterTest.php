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
use Horde\Log\Filter\ExactLevelFilter;
use Horde\Log\LogFilter;
use Horde\Log\LogLevel;
use Horde\Log\LogMessage;
use TypeError;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ExactLevelFilter::class)]
class ExactLevelFilterTest extends TestCase
{
    private LogLevel $messageLogLevel1;
    private LogLevel $messageLogLevel2;
    private LogLevel $messageLogLevel3;
    private LogMessage $messageLvl3;
    private LogMessage $messageLvl2;
    private LogMessage $messageLvl1;
    private ExactLevelFilter $filter;
    private ExactLevelFilter $filter2;
    private ExactLevelFilter $filter3;

    public function setUp(): void
    {
        // accept at and only at level 2
        $this->messageLogLevel1 = new LogLevel(1, 'fatal');
        $this->messageLogLevel2 = new LogLevel(2, 'error');
        $this->messageLogLevel3 = new LogLevel(3, 'warn');
        $this->messageLvl3 = new LogMessage($this->messageLogLevel3, 'test');
        $this->messageLvl2 = new LogMessage($this->messageLogLevel2, 'error');
        $this->messageLvl1 = new LogMessage($this->messageLogLevel1, 'test');
        $this->filter = new ExactLevelFilter(2);
        $this->filter2 = new ExactLevelFilter(2, 'error');
        $this->filter3 = new ExactLevelFilter(2, 'ERROR');
    }

    public function testLevelFilterAccept()
    {
        $this->assertTrue($this->filter->accept($this->messageLvl2));
        $this->assertTrue($this->filter2->accept($this->messageLvl2));
    }

    public function testLevelFilterReject()
    {
        $this->assertFalse($this->filter->accept($this->messageLvl3));
        $this->assertFalse($this->filter->accept($this->messageLvl1));
        $this->assertFalse($this->filter2->accept($this->messageLvl1));
        $this->assertFalse($this->filter3->accept($this->messageLvl2));
    }

    public function testConstructorThrowsOnInvalidLevel()
    {
        $this->expectException(TypeError::class);
        new ExactLevelFilter('foo', 'bar');
    }
}
