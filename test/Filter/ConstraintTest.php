<?php

/**
 * Horde Log package
 *
 * @author     James Pepin <james@jamespepin.com>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test\Filter;

use Horde_Log_Filter_Constraint;
use Horde_Constraint_AlwaysFalse;
use PHPUnit\Framework\TestCase;

/**
 * @author     James Pepin <james@jamespepin.com>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
*/ 
use PHPUnit\Framework\Attributes\CoversNothing;

#[coversnothing]
class ConstraintTest extends TestCase
{
    public function testFilterDoesNotAcceptWhenRequiredFieldIsMissing()
    {
        $event = [
            'someotherfield' => 'other value',
        ];
        $filterator = new Horde_Log_Filter_Constraint();
        $filterator->addRequiredField('required_field');

        $this->assertFalse($filterator->accept($event));
    }

    public function testFilterAcceptsWhenRequiredFieldisPresent()
    {
        $event = [
            'required_field' => 'somevalue',
            'someotherfield' => 'other value',
        ];
        $filterator = new Horde_Log_Filter_Constraint();
        $filterator->addRequiredField('required_field');

        $this->assertTrue($filterator->accept($event));
    }

    public function testFilterAcceptsWhenRegexMatchesField()
    {
        $event = [
            'regex_field'    => 'somevalue',
            'someotherfield' => 'other value',
        ];
        $filterator = new Horde_Log_Filter_Constraint();
        $filterator->addRegex('regex_field', '/somevalue/');

        $this->assertTrue($filterator->accept($event));
    }

    public function testFilterAcceptsWhenRegex_DOESNOT_MatcheField()
    {
        $event = [
            'regex_field'    => 'somevalue',
            'someotherfield' => 'other value',
        ];
        $filterator = new Horde_Log_Filter_Constraint();
        $filterator->addRegex('regex_field', '/someothervalue/');

        $this->assertFalse($filterator->accept($event));
    }

    private function getConstraintMock($returnVal)
    {
        $const = $this->getMockBuilder('Horde_Constraint', ['evaluate'])->getMock();
        $const->expects($this->once())
            ->method('evaluate')
            ->willReturn($returnVal);
        return $const;
    }

    public function testFilterCallsEvalOnAllConstraintsWhenTheyAreAllTrue()
    {
        $filterator = new Horde_Log_Filter_Constraint();
        $filterator->addConstraint('fieldname', $this->getConstraintMock(true));
        $filterator->addConstraint('fieldname', $this->getConstraintMock(true));

        $filterator->accept(['fieldname' => 'foo']);
    }

    public function testFilterStopsWhenItFindsAFalseCondition()
    {
        $filterator = new Horde_Log_Filter_Constraint();
        $filterator->addConstraint('fieldname', $this->getConstraintMock(true));
        $filterator->addConstraint('fieldname', $this->getConstraintMock(true));
        $filterator->addConstraint('fieldname', new Horde_Constraint_AlwaysFalse());

        $const = $this->getMockBuilder('Horde_Constraint', ['evaluate'])->getMock();
        $const->expects($this->never())
            ->method('evaluate');
        $filterator->addConstraint('fieldname', $const);
        $filterator->accept(['fieldname' => 'foo']);

    }

    public function testFilterAcceptCallsConstraintOnNullWhenFieldDoesnotExist()
    {
        $filterator = new Horde_Log_Filter_Constraint();
        $const = $this->getMockBuilder('Horde_Constraint', ['evaluate'])->getMock();
        $const->expects($this->once())
            ->method('evaluate')
            ->with(null);
        $filterator->addConstraint('fieldname', $const);
        $filterator->accept(['someotherfield' => 'foo']);
    }
}
