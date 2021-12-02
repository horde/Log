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

namespace Horde\Log\Test\Formatter;

use PHPUnit\Framework\TestCase;
use Horde_Log;
use Horde_Log_Formatter_Simple;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */
class SimpleTest extends TestCase
{
    public function testConstructorThrowsOnBadFormatString()
    {
        $this->expectException('InvalidArgumentException');
        new Horde_Log_Formatter_Simple(1);
    }

    public function testDefaultFormat()
    {
        $f = new Horde_Log_Formatter_Simple();
        $line = $f->format(array(
            'message' => $message = 'message',
            'level' => $level = Horde_Log::ALERT,
            'levelName' => $levelName = 'ALERT'
        ));

        $this->assertStringContainsString($message, $line);
        $this->assertStringContainsString($levelName, $line);
    }
}
