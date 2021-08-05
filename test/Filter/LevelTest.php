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

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */
class LevelTest extends TestCase
{
    public function setUp(): void
    {
        // accept at or below level 2
        $this->filter = new Horde_Log_Filter_Level(2);
    }

    public function testLevelFilterAccept()
    {
        $this->assertTrue($this->filter->accept(array('message' => '', 'level' => 2)));
        $this->assertTrue($this->filter->accept(array('message' => '', 'level' => 1)));
    }

    public function testLevelFilterReject()
    {
        $this->assertFalse($this->filter->accept(array('message' => '', 'level' => 3)));
    }

    public function testConstructorThrowsOnInvalidLevel()
    {
        $this->expectException('InvalidArgumentException');
        new Horde_Log_Filter_Level('foo');
    }
}
