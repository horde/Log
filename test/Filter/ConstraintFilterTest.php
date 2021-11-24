<?php
/**
 * Horde Log package
 *
 * @author     Rafael te Boekhorst <boekhorstb1s@b1-systems.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test\Filter;

use PHPUnit\Framework\TestCase;

use Horde\Log\Filter\ConstraintFilter;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use Horde_Constraint_AlwaysFalse;

class ConstraintFilterTest extends TestCase
{
    public function setUp(): void
    {
        $this->level1 = new LogLevel(1, 'testName1');
        $this->level2 = new LogLevel(2, 'testName2');
        $this->level3 = new LogLevel(3, 'testName3');
        $this->level4 = new LogLevel(4, 'testName4');
        $this->message1 = 'testMessage1';
        $this->message2 = 'required_field';
        $this->message3 = 'somevalue';
        $this->message4 = 'multiple required fields';
        $this->logMessage1 = new LogMessage($this->level1, $this->message1);
        $this->logMessage2 = new LogMessage($this->level2, $this->message2);
        $this->logMessage3 = new LogMessage($this->level3, $this->message3, ['customField3' => 'custumValue3']);
        $this->logMessage4 = new LogMessage($this->level4, $this->message4, ['customField4' => 'custumValue4', 'customField5' => 'custumValue5']);
    }

    public function testFilterAcceptMultipleRequiredFieldsIfPresent()
    {
        $filterator = new ConstraintFilter();
        $filterator->addRequiredFields('customField4', 'customField5');
        $this->assertTrue($filterator->accept($this->logMessage4));
    }

    public function testFilterNotAcceptMultipleRequiredFieldsIfNotPresent()
    {
        $filterator = new ConstraintFilter();
        $filterator->addRequiredFields('customField3', 'customField4');
        $this->assertFalse($filterator->accept($this->logMessage4));
    }


    public function testFilterDoesNotAcceptWhenRequiredFieldIsMissing()
    {
        $filterator = new ConstraintFilter();
        $filterator->addRequiredField('required_field');
        $this->assertFalse($filterator->accept($this->logMessage3));
    }


    public function testFilterAcceptsWhenRequiredFieldisPresent()
    {
        $filterator = new ConstraintFilter();
        $filterator->addRequiredField('customField3');
        $this->assertTrue($filterator->accept($this->logMessage3));
    }

    public function testFilterAcceptsWhenRegexMatchesField()
    {
        $filterator = new ConstraintFilter();
        $filterator->addRegex('customField3', '/cust*/');

        $this->assertTrue($filterator->accept($this->logMessage3));
    }

    public function testFilterAcceptsWhenRegex_DOESNOT_MatcheField()
    {
        $filterator = new ConstraintFilter();
        $filterator->addRegex('customField3', '/this value does not exist/');

        $this->assertFalse($filterator->accept($this->logMessage3));
    }

    private function getConstraintMock($returnVal)
    {
        $const = $this->getMockBuilder('Horde_Constraint', array('evaluate'))->getMock();
        $const->expects($this->once())
            ->method('evaluate')
            ->will($this->returnValue($returnVal));
        return $const;
    }

    public function testFilterCallsEvalOnAllConstraintsWhenTheyAreAllTrue()
    {
        $filterator = new ConstraintFilter();
        $filterator->addConstraint('context', $this->getConstraintMock(true));
        $filterator->addConstraint('level', $this->getConstraintMock(true));
        $filterator->addConstraint('message', $this->getConstraintMock(true));
        $filterator->addConstraint('dafdfadg234435dafdf', $this->getConstraintMock(true));

        $filterator->accept($this->logMessage3);
    }

    public function testFilterStopsWhenItFindsAFalseCondition()
    {
        $filterator = new ConstraintFilter();
        $filterator->addConstraint('fieldname', $this->getConstraintMock(true));
        $filterator->addConstraint('fieldname', $this->getConstraintMock(true));
        $filterator->addConstraint('fieldname', new Horde_Constraint_AlwaysFalse());

        $const = $this->getMockBuilder('Horde_Constraint', array('evaluate'))->getMock();
        $const->expects($this->never())
            ->method('evaluate');
        $filterator->addConstraint('fieldname', $const);
        $filterator->accept($this->logMessage3);
    }

    public function testFilterAcceptCallsConstraintOnNullWhenFieldDoesnotExist()
    {
        $filterator = new ConstraintFilter();
        $const = $this->getMockBuilder('Horde_Constraint', array('evaluate'))->getMock();
        $const->expects($this->once())
            ->method('evaluate')
            ->with(null);
        $filterator->addConstraint('non existant field', $const);
        $filterator->accept($this->logMessage2);
    }
}
