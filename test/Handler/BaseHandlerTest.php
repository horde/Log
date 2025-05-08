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

use Horde\Log\Handler\BaseHandler;
// To test systems which interact with a handler
use Horde\Log\Handler\MockHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Horde_Log;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use Horde\Log\Filter\ConstraintFilter;
use Horde\Log\Filter\MessageFilter;

// Test Helper for cases in which the BaseHandler is the UUT
class BaseHandlerImplementation extends BaseHandler
{
    public function write(LogMessage $event): bool
    {
        return true;
    }

    public function setOption($optionKey, $optionValue): bool
    {
        return true;
    }
}

/**
 * @coversNothing
 */
class BaseHandlerTest extends TestCase
{
    public function setUp(): void
    {
        # Bult in Mock for abstract classes (in phpunit)
        $this->baseHandlerMock = $this->createMock(BaseHandler::class);

        # Own Mock class for testing the base class
        $this->mockhandler = new MockHandler();

        $this->level1 = new LogLevel(Horde_Log::ALERT, 'Alert');
        $this->message1 = 'this is an emergency!';
        $this->logMessage1 = new LogMessage($this->level1, $this->message1, ['randomfield' => 'stuff']);
        $this->constraintFilter = new ConstraintFilter();
    }

    // This test breaks in phpunit 12 and is not trivial to fix without further work
    public function testWriteFunctionIsExecutedByLog(): void
    {
        // Traditionally we used a mock created by getMockForAbstractClass() which is deprecated
        // We cannot use the BaseHandlerMock here because we really want to test the BaseHandler
        // We can use the NullHandler as it does not implement/override log()
        // As opposed to the MockHandler which does override log() :D

        $baseHandlerMock = $this->getMockBuilder(BaseHandlerImplementation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['write'])
            ->getMock();
        // We need to set the log level to a higher level than the one we are testing
        $baseHandlerMock->expects($this->once())->method('write');
        $baseHandlerMock->log($this->logMessage1);
    }

    public function testWriteFunctionsReturnsBoolean(): void
    {
        // Note: Phpunit gives a default false as output of abstract methods
        $this->assertFalse($this->baseHandlerMock->write($this->logMessage1));
    }

    #[DoesNotPerformAssertions]
    public function testFilterMethodExistsAndDoesNotFail(): void
    {
        $this->baseHandlerMock->addFilter($this->constraintFilter);
    }

    public function testIfLogMethodUsesFiltersByUsingMockhandler(): void
    {
        // reassigning defaul mockhandler to a local variable
        $mockhandler = $this->mockhandler;

        // creating a message that IS going to be logged
        $level1 = new LogLevel(Horde_Log::ALERT, 'Alert');
        $message1 = 'this is an emergency!';
        $logMessage1 = new LogMessage($level1, $message1, ['randomfield' => 'stuff']);

        // creating a message that is NOT going to be logged
        $level2 = new LogLevel(Horde_Log::CRITICAL, 'Critical');
        $message2 = 'this is not going to be logged!';
        $logMessage2 = new LogMessage($level2, $message2, ['randomfield' => 'stuff']);

        // creating a message filter for the BaseHandler
        $messageFilter = new MessageFilter('/emergency/');
        $mockhandler->addFilter($messageFilter);

        // Check that this will filter message1
        $mockhandler->log($logMessage1);
        $this->assertEquals($mockhandler->check, $logMessage1);

        // Check that this will not filter message2
        $mockhandler->log($logMessage2);
        $this->assertEquals($mockhandler->check, 'filtered out');
    }
}
