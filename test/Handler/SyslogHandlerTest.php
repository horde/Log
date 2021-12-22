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

namespace Horde\Log\Test\Handler;

use Horde\Log\Handler\SyslogHandler;
use Horde\Log\Handler\SyslogOptions;
use PHPUnit\Framework\TestCase;
use Horde\Log\LogException;
use Horde_Log;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;

class SyslogHandlerTest extends TestCase
{
    public function setUp(): void
    {
        date_default_timezone_set('America/New_York');
        $this->level1 = new LogLevel(Horde_Log::ALERT, 'Alert');
        $this->message1 = 'this is an emergency!';
        $this->logMessage1 = new LogMessage($this->level1, $this->message1, ['timestamp' => date('c')]);
        $this->logMessage1->formatMessage([]);
        $this->logMessage2 = new LogMessage($this->level1, $this->message1, ['timestamp' => date('c')]);
        $this->syshandler = new SyslogHandler();
    }


    public function testWrite()
    {
        $this->assertTrue($this->syshandler->write($this->logMessage1));
    }

    # NB: have to call formatMessage with [] as a formatter. Should this not be done in the code of Sysloghandler.php?
    public function testIfMessageIsFormatted(): void
    {
        $this->expectException(LogException::class);
        $this->syshandler->setOption('ident', 'Message to terminal" ');
        $this->syshandler->setOption('openlogOptions', LOG_PERROR);
        $this->syshandler->write($this->logMessage2);
    }

    public function testBadOptionKeyThrowsError()
    {
        $this->expectException(LogException::class);
        $this->syshandler->setOption('', '');
    }

    public function testIfSyslogOptionsAreSet()
    {
        $options = new SyslogOptions();
        $this->assertEquals(LOG_ERR, $options->defaultPriority);
        $this->assertEquals(LOG_USER, $options->facility);
        $this->assertEquals(LOG_ODELAY, $options->openLogOptions);
    }


    public function testIndentErrorInitializeSyslog(): void
    {
        $this->expectException(LogException::class);
        $this->syshandler->setOption('ident', 2);
        $this->syshandler->setOption('openlogOptions', 1);
        $this->syshandler->write($this->logMessage1);
    }

    public function testOptionsErrorInitializeSyslog(): void
    {
        $this->expectException(LogException::class);
        $this->syshandler->setOption('ident', 'some error message');
        $this->syshandler->setOption('openlogOptions', 'this should be a log constant or at least an integer');
        $this->syshandler->write($this->logMessage1);
    }

    # I have not found a way to make the function syslog() throw errors (it is located within the if-satement of the write()-method...). That would be needed to test the errormessages
    public function testSysLogErrorThrows()
    {
        $this->markTestSkipped('should be revisited?');
    }
}
