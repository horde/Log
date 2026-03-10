<?php

/**
 * Horde Log package.
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

namespace Horde\Log\Test;

use Horde\Log\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Horde\Util\HordeString;
use Psr\Log\InvalidArgumentException;
use Stringable;
use Horde\Log\Filter\MessageFilter;
use Horde\Log\Handler\MockHandler;
use Horde\Log\Handler\SyslogHandler;
use ReflectionClass;
use Horde\Log\LogHandler;
use Horde\Log\LogException;
use Horde_Log;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use Horde\Log\LogLevels;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Logger::class)]
class LoggerTest extends TestCase
{
    private LogLevel $level1;
    private string $message1;
    private string $message2;
    private LogMessage $logMessage1;
    private LogMessage $logMessage2;
    private LogLevels $loglevelsss;
    private array $messagefilter = [];
    private MockHandler $mockhandler1;
    private array $handlers = [];
    private Logger $logging;

    public function setUp(): void
    {
        date_default_timezone_set('America/New_York');
        $this->level1 = new LogLevel(Horde_Log::ALERT, 'Alert');
        $this->message1 = 'this is an emergency!';
        $this->message2 = 'this is a polar bear!';
        $this->logMessage1 = new LogMessage($this->level1, $this->message1, ['timestamp' => date('c')]);
        $this->logMessage2 = new LogMessage($this->level1, $this->message2);

        $this->loglevelsss = new LogLevels();
        $this->messagefilter[] = new MessageFilter('/emergency/');

        $this->mockhandler1 = new MockHandler();
        $this->handlers[] = $this->mockhandler1;


        $this->logging = new Logger($this->handlers, null, $this->messagefilter);
    }

    public function testSerializeAndAddFilter(): void
    {
        $data = $this->logging->serialize();
        $this->assertStringContainsString('MockHandler', $data);
        $this->assertStringContainsString('MessageFilter', $data);
        $this->assertStringContainsString('/emergency/', $data);
    }

    public function testSerializeAndAddHandler(): void
    {
        $mockhandler3 = new MockHandler();
        $mockhandler3->addFilter(new MessageFilter('/whazzaaa/'));
        $this->logging->addHandler($mockhandler3);
        $data = $this->logging->serialize();
        $this->assertStringContainsString('/whazzaaa/', $data);
    }

    public function testUnserialize(): void
    {
        // Create the message that should be filtered out and a first logger
        $level = new LogLevel(Horde_Log::ALERT, 'Alert');
        $message1 = 'this message will not be logged because the MessageFilter will remove it';
        $message2 = 'buzzword: testUnserialize, this message will be logged successfully';
        $logger = new Logger();

        // test if serialized instance of logger contains filter and handler
        $mockhandler4 = new MockHandler();
        $mockhandler4->addFilter(new MessageFilter('/testUnserialize/'));
        $logger->addHandler($mockhandler4);
        $data = $logger->serialize();
        $this->assertStringContainsString('/testUnserialize/', $data);

        // unserialize data with a new logger, serialize it again and check if message1 is filtered out correctly and message2 is logged correctly
        // - NB: the $check value in the Mockhandler is used to verify this
        $newlogger = new Logger();
        $newlogger->unserialize($data);
        $newlogger->log($level, $message1);
        $newdata = $newlogger->serialize();
        $this->assertStringContainsString('filtered out', $newdata);
        $newlogger->log($level, $message2);
        $newdata = $newlogger->serialize();
        $this->assertStringContainsString('successfully', $newdata);
    }

    public function testErrorsUnserialize(): void
    {
        // list of wrong data that is serialized
        $data[] = ['this is not a string'];
        $data[] = serialize('blabla');
        $data[] = serialize([]);
        $data[] = serialize(['another version']);
        $count = 0;

        // test that all array-entries have wrong data and throw errors
        foreach ($data as $value) {
            try {
                $this->logging->unserialize($value);
            } catch (\Throwable $th) {
                $count++;
                $this->assertInstanceOf(LogException::class, $th);
            }
        }

        // the amount of throws needs to be equal to the lenght of the array data[]
        $this->assertEquals(count($data), $count);
    }

    /*
    * Test all the log methods of the logger
    * - Uses a new instance of a logger
    * - uses get_class_methods(LoggerInterface::class) to load implementes log-methods of the LoggerInterface
    */
    public function testAllLogMethods()
    {
        // creating a new logger with mockhandler and MessageFilter
        $mockhandler1 = new MockHandler();
        $mockhandler1->addFilter(new MessageFilter('/logmethodscheck/'));
        $handlers[] = $mockhandler1;
        $logger = new Logger($handlers);

        // create some messages and levels
        $level1 = new LogLevel(Horde_Log::ALERT, 'Alert');
        $message1 = 'this is a valid message for logmethodscheck';

        // put all the methods fromt the LoggerInterface::class in an array
        $loggerinterface_methods = get_class_methods(LoggerInterface::class);

        // check for each method that the correct name, message and level are set.
        // Because $mockhandler1 is loaded into $logger, $mockhandler1->check should containt what is logged through $logger->log($somelevel, $somemessage)
        foreach ($loggerinterface_methods as $key => $method) {
            if ($method == 'log') {
                $logger->$method($level1, "logmethodscheck: for the log method");
                $this->assertEquals($mockhandler1->check->message(), "logmethodscheck: for the log method");
                $this->assertEquals($mockhandler1->check->level()->name(), $level1->name());
            } else {
                $logger->$method($message1);
                $this->assertEquals($mockhandler1->check->message(), $message1);
                $this->assertEquals($mockhandler1->check->level()->name(), $method);
            }
        }
    }

    /*
    * Test if the log() function can pass a string instead of an instance of LogMessage or Stringable
    * - Uses the default Logger (in SetUp()).
    *
    * Note:
    * - default Logger uses a mockhandler1 and an additional filter with "emergency"
    * - mockhandler1 will log a message if the $this->logging->log() method is called with "emergency" in it
    * - mockhandler1->check will not hold the value "filtered out" because the filter is defined in the logger not in the handler
    */
    public function testStringAsMessageForLog()
    {
        $message = "emergency: this is a a message for an emergency!";
        $this->logging->log($this->level1, $message);
        $this->assertEquals($this->mockhandler1->check->message(), $message);
    }

    /*
    * Test if the log() function can use its own filters independently of the mockhandlers filters
    * - Uses two new loggers: $logger and $newlogger
    *
    */
    public function testFiltersOfLoggerWorkWithoutMockHandlerFilter()
    {
        // define logger that loads mockhandlers
        $mockhandlerWihtoutFilter = new MockHandler();
        $messagefilter[] = new MessageFilter('/filter this out/');
        $handlers[] = $mockhandlerWihtoutFilter;
        $logger = new Logger($handlers, null, $messagefilter);

        // random messages
        $message1 = "filter this out please, because it is important";
        $logMessage1 = new LogMessage($this->level1, $message1);

        // $message1 is filtered out by the logger
        $logger->log($this->level1, $message1);

        $data = $logger->serialize();
        $this->assertStringNotContainsString($data, $message1);

        // $message1 is not filtered out by the mockhandler
        $mockhandlerWihtoutFilter->log($logMessage1);
        $this->assertEquals($message1, $mockhandlerWihtoutFilter->check->message());
    }

    public function testLogMethodThrowsErrorIfWrongLogCode()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->logging->log(22, $this->message1);
    }

    public function testLogMethodThrowsErrorIfWrongLogLevelString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->logging->log("nonexistant level", $this->message1);
    }

    public function testConstructorWithEmptyHandlers(): void
    {
        $logger = new Logger([]);
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testConstructorWithMultipleHandlers(): void
    {
        $handler1 = new MockHandler();
        $handler2 = new MockHandler();
        $logger = new Logger([$handler1, $handler2]);

        // Log a message and verify both handlers received it
        $logger->info('test message');

        $this->assertInstanceOf(LogMessage::class, $handler1->check);
        $this->assertInstanceOf(LogMessage::class, $handler2->check);
        $this->assertEquals('test message', $handler1->check->message());
        $this->assertEquals('test message', $handler2->check->message());
    }

    public function testConstructorWithCustomLevels(): void
    {
        $customLevels = new LogLevels();
        $logger = new Logger([], $customLevels);
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testAddHandlerAcceptsLogHandler(): void
    {
        $logger = new Logger();
        $handler = new MockHandler();
        $logger->addHandler($handler);

        $logger->info('test');
        $this->assertEquals('test', $handler->check->message());
    }

    public function testAddFilterAcceptsLogFilter(): void
    {
        $logger = new Logger([new MockHandler()]);
        $filter = new MessageFilter('/blocked/');
        $logger->addFilter($filter);

        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testLogWithIntegerLevel(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $logger->log(Horde_Log::INFO, 'info message');

        $this->assertEquals('info message', $handler->check->message());
        $this->assertEquals('info', $handler->check->level()->name());
    }

    public function testLogWithStringLevel(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $logger->log('warning', 'warning message');

        $this->assertEquals('warning message', $handler->check->message());
        $this->assertEquals('warning', $handler->check->level()->name());
    }

    public function testLogWithLogLevelObject(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);
        $level = new LogLevel(Horde_Log::ERROR, 'error');

        $logger->log($level, 'error message');

        $this->assertEquals('error message', $handler->check->message());
        $this->assertEquals('error', $handler->check->level()->name());
    }

    public function testLogWithStringableMessage(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable message';
            }
        };

        $logger->info($stringable);

        $this->assertEquals('stringable message', $handler->check->message());
    }

    public function testLogWithLogMessageObject(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $level = new LogLevel(Horde_Log::NOTICE, 'notice');
        $logMessage = new LogMessage($level, 'original message', ['key' => 'value']);

        $logger->log($level, $logMessage);

        $this->assertEquals('original message', $handler->check->message());
        $this->assertEquals('notice', $handler->check->level()->name());
    }

    public function testLogDelegatesToAllHandlers(): void
    {
        $handler1 = new MockHandler();
        $handler2 = new MockHandler();
        $handler3 = new MockHandler();
        $logger = new Logger([$handler1, $handler2, $handler3]);

        $logger->debug('debug message');

        $this->assertEquals('debug message', $handler1->check->message());
        $this->assertEquals('debug message', $handler2->check->message());
        $this->assertEquals('debug message', $handler3->check->message());
    }

    public function testFiltersRejectMessages(): void
    {
        $handler = new MockHandler();
        $filter = new MessageFilter('/accept/');
        $logger = new Logger([$handler], null, [$filter]);

        // This message should be filtered out - handler's events should be empty
        $logger->info('reject this message');
        $this->assertEmpty($handler->events);

        // This message should pass the filter
        $logger->info('accept this message');
        $this->assertCount(1, $handler->events);
        $this->assertEquals('accept this message', $handler->events[0]->message());
    }

    public function testContextMergingWithLogMessage(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $level = new LogLevel(Horde_Log::INFO, 'info');
        $logMessage = new LogMessage($level, 'message', ['key1' => 'value1']);

        // Additional context should be merged
        $logger->log($level, $logMessage, ['key2' => 'value2']);

        $context = $handler->check->context();
        $this->assertArrayHasKey('key1', $context);
        $this->assertArrayHasKey('key2', $context);
    }

    public function testInvalidLevelTypeThrowsException(): void
    {
        $logger = new Logger([new MockHandler()]);

        $this->expectException(InvalidArgumentException::class);
        $logger->log([], 'message');  // Array is not valid level type
    }

    public function testPsr3EmergencyMethod(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $logger->emergency('emergency message', ['context' => 'value']);

        $this->assertEquals('emergency message', $handler->check->message());
        $this->assertEquals('emergency', $handler->check->level()->name());
        $this->assertArrayHasKey('context', $handler->check->context());
    }

    public function testPsr3AlertMethod(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $logger->alert('alert message');

        $this->assertEquals('alert message', $handler->check->message());
        $this->assertEquals('alert', $handler->check->level()->name());
    }

    public function testPsr3CriticalMethod(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $logger->critical('critical message');

        $this->assertEquals('critical message', $handler->check->message());
        $this->assertEquals('critical', $handler->check->level()->name());
    }

    public function testPsr3ErrorMethod(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $logger->error('error message');

        $this->assertEquals('error message', $handler->check->message());
        $this->assertEquals('error', $handler->check->level()->name());
    }

    public function testPsr3WarningMethod(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $logger->warning('warning message');

        $this->assertEquals('warning message', $handler->check->message());
        $this->assertEquals('warning', $handler->check->level()->name());
    }

    public function testPsr3NoticeMethod(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $logger->notice('notice message');

        $this->assertEquals('notice message', $handler->check->message());
        $this->assertEquals('notice', $handler->check->level()->name());
    }

    public function testPsr3InfoMethod(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $logger->info('info message');

        $this->assertEquals('info message', $handler->check->message());
        $this->assertEquals('info', $handler->check->level()->name());
    }

    public function testPsr3DebugMethod(): void
    {
        $handler = new MockHandler();
        $logger = new Logger([$handler]);

        $logger->debug('debug message');

        $this->assertEquals('debug message', $handler->check->message());
        $this->assertEquals('debug', $handler->check->level()->name());
    }
}
