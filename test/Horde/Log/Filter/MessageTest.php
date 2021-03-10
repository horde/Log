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
namespace Horde\Log\Filter;
use \PHPUnit\Framework\TestCase;
use \Horde_Log_Filter_Message;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */
class MessageTest extends TestCase
{

    public function testMessageFilterRecognizesInvalidRegularExpression()
    {
        $this->expectException('InvalidArgumentException');
        new Horde_Log_Filter_Message('invalid regexp');
    }

    public function testMessageFilter()
    {
        $filter = new Horde_Log_Filter_Message('/accept/');
        $this->assertTrue($filter->accept(array('message' => 'foo accept bar', 'level' => 0)));
        $this->assertFalse($filter->accept(array('message' => 'foo reject bar', 'level' => 0)));
    }

}
