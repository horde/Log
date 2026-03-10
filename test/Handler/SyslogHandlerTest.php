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
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SyslogHandler::class)]
class SyslogHandlerTest extends TestCase
{
    private LogLevel $level1;
    private string $message1;
    private LogMessage $logMessage1;
    private LogMessage $logMessage2;
    private SyslogHandler $syshandler;

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

    public function testConstructorWithCustomOptions(): void
    {
        $options = new SyslogOptions();
        $options->ident = 'test-app';
        $options->facility = LOG_LOCAL0;
        $options->openLogOptions = LOG_PID | LOG_PERROR;

        $handler = new SyslogHandler($options);
        $this->assertInstanceOf(SyslogHandler::class, $handler);
    }

    public function testWriteAllLogLevels(): void
    {
        $handler = new SyslogHandler();

        $levels = [
            Horde_Log::EMERG => 'emergency',
            Horde_Log::ALERT => 'alert',
            Horde_Log::CRIT => 'critical',
            Horde_Log::ERR => 'error',
            Horde_Log::WARN => 'warning',
            Horde_Log::NOTICE => 'notice',
            Horde_Log::INFO => 'info',
            Horde_Log::DEBUG => 'debug',
        ];

        foreach ($levels as $priority => $name) {
            $level = new LogLevel($priority, $name);
            $message = new LogMessage($level, "Test $name message");
            $message->formatMessage([]);
            $this->assertTrue($handler->write($message));
        }
    }

    public function testPriorityMappingToSyslog(): void
    {
        $handler = new SyslogHandler();

        // Test that Horde_Log priorities map correctly
        $level = new LogLevel(Horde_Log::ERR, 'error');
        $message = new LogMessage($level, 'error test');
        $message->formatMessage([]);

        $this->assertTrue($handler->write($message));
        $this->assertEquals(Horde_Log::ERR, $level->criticality());
    }

    public function testFacilityChange(): void
    {
        $options1 = new SyslogOptions();
        $options1->ident = 'test1';
        $options1->facility = LOG_USER;

        $handler = new SyslogHandler($options1);
        $handler->write($this->logMessage1);

        // Change facility - should reinitialize
        $handler->setOption('facility', LOG_LOCAL0);
        $this->assertTrue($handler->write($this->logMessage1));
    }

    public function testIdentChange(): void
    {
        $handler = new SyslogHandler();
        $handler->write($this->logMessage1);

        // Change ident - should reinitialize
        $handler->setOption('ident', 'changed-ident');
        $this->assertTrue($handler->write($this->logMessage1));
    }

    public function testWriteWithFilters(): void
    {
        $handler = new SyslogHandler();

        // Add a filter that rejects everything
        $filter = new class implements \Horde\Log\LogFilter {
            public function accept(\Horde\Log\LogMessage $event): bool
            {
                return false;
            }
        };
        $handler->addFilter($filter);

        // log() should return without writing (filter rejects)
        $handler->log($this->logMessage1);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testWriteWithFormatters(): void
    {
        $formatter = new class implements \Horde\Log\LogFormatter {
            public function format(\Horde\Log\LogMessage $event): string
            {
                return '[FORMATTED] ' . $event->message();
            }
        };

        $handler = new SyslogHandler(null, [$formatter]);
        $this->assertTrue($handler->write($this->logMessage1));
    }

    public function testDefaultOptionsValues(): void
    {
        $options = new SyslogOptions();

        $this->assertEquals(LOG_ERR, $options->defaultPriority);
        $this->assertEquals(LOG_USER, $options->facility);
        $this->assertEquals(LOG_ODELAY, $options->openLogOptions);
        $this->assertIsString($options->ident);
    }

    public function testMultipleWrites(): void
    {
        $handler = new SyslogHandler();

        for ($i = 0; $i < 5; $i++) {
            $level = new LogLevel(Horde_Log::INFO, 'info');
            $message = new LogMessage($level, "Message number $i");
            $message->formatMessage([]);
            $this->assertTrue($handler->write($message));
        }
    }

    public function testOpenlogOptionsApplied(): void
    {
        $options = new SyslogOptions();
        $options->openLogOptions = LOG_PID | LOG_CONS;

        $handler = new SyslogHandler($options);
        $this->assertTrue($handler->write($this->logMessage1));
    }
}
