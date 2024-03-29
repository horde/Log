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
use \PHPUnit\Framework\TestCase;
use \Horde_Log_Filter_Level;
use \Horde_Log_Filter_ExactLevel;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */
class ExactLevelTest extends TestCase
{
    public function setUp(): void
    {
        // accept at and only at level 2
        $this->filter = new Horde_Log_Filter_ExactLevel(2);
    }

    public function testLevelFilterAccept()
    {
        $this->assertTrue($this->filter->accept(array('message' => '', 'level' => 2)));
    }

    public function testLevelFilterReject()
    {
        $this->assertFalse($this->filter->accept(array('message' => '', 'level' => 1)));
        $this->assertFalse($this->filter->accept(array('message' => '', 'level' => 3)));
    }

    public function testConstructorThrowsOnInvalidLevel()
    {
        $this->expectException('InvalidArgumentException');
        new Horde_Log_Filter_Level('foo');
    }
}
