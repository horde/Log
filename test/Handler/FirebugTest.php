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
namespace Horde\Log\Test\Handler;
use \PHPUnit\Framework\TestCase;
use \Horde_Log;
use \Horde_Log_Handler_Stream;
use \Horde_Log_Handler_Firebug;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */
class FirebugTest extends TestCase
{
    public function setUp(): void
    {
        date_default_timezone_set('America/New_York');
    }

    public function testSettingBadOptionThrows()
    {
        $this->expectException('Horde_Log_Exception');
        $handler = new Horde_Log_Handler_Stream('php://memory');
        $handler->setOption('foo', 42);
    }

    public function testWrite()
    {
        ob_start();

        $handler = new Horde_Log_Handler_Firebug();
        $handler->write(array('message' => $message = 'message-to-log',
                              'level' => $level = Horde_Log::ALERT,
                              'levelName' => $levelName = 'ALERT',
                              'timestamp' => date('c')));

        $contents = ob_get_clean();

        $date = '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}-\d{2}:\d{2}';

        $this->assertMatchesRegularExpression("/console.error\(\"$date $levelName: $message\"\);/", $contents);
    }

}
