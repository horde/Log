<?php
/**
 * @author     James Pepin <james@jamespepin.com>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Filters
 */
declare(strict_types=1);

namespace Horde\Log\Filter;

use Horde\Log\LogFilter;
use Horde\Log\LogMessage;
use Horde_Constraint_Coupler;
use Horde_Constraint_PregMatch;
use Horde_Constraint_And;
use Horde_Constraint_Not;
use Horde_Constraint_Null;
use Horde_Constraint;

/**
 * Filters log events using defined constraints on one or more fields of the
 * $event array.
 *
 * @author     James Pepin <james@jamespepin.com>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Filters
 *
 * @todo Implement constraint objects for the different types of filtering ie
 * regex,required,type..etc..  so we can add different constaints ad infinitum.
 */
class ConstraintFilter implements LogFilter
{
    /**
     * Constraint list.
     *
     * @var Horde_Constraint_Coupler[]
     */
    protected $constraints = [];

    /**
     * Default constraint coupler.
     *
     * @var Horde_Constraint_Coupler
     * @default Horde_Constraint_And
     */
    protected $coupler;

    /**
     * Constructor.
     *
     * @param Horde_Constraint_Coupler $coupler  The default kind of
     *                                           constraint to use to couple
     *                                           multiple constraints.
     *                                           Defaults to And.
     */
    public function __construct(Horde_Constraint_Coupler $coupler = null)
    {
        $this->coupler = is_null($coupler)
            ? new Horde_Constraint_And()
            : $coupler;
    }

    /**
     * Add a constraint to the filter.
     *
     * @param string $field                 The field to apply the constraint
     *                                      to.
     * @param Horde_Constraint $constraint  The constraint to apply.
     *
     * @return ConstraintFilter  A reference to $this to allow
     *                                      method chaining.
     */
    public function addConstraint($field, Horde_Constraint $constraint): self
    {
        if (!isset($this->constraints[$field])) {
            $this->constraints[$field] = clone($this->coupler);
        }
        $this->constraints[$field]->addConstraint($constraint);

        return $this;
    }

    /**
     * Add a regular expression to filter by.
     *
     * Takes a field name and a regex, if the regex does not match then the
     * event is filtered.
     *
     * @param string $field  The name of the field that should be part of the
     *                       event.
     * @param string $regex  The regular expression to filter by.
     * @return ConstraintFilter  A reference to $this to allow
     *                                      method chaining.
     */
    public function addRegex($field, $regex): self
    {
        return $this->addConstraint($field, new Horde_Constraint_PregMatch($regex));
    }

    /**
     * Add a required field to the filter.
     *
     * If the field does not exist on the event, then it is filtered.
     *
     * @param string $field  The name of the field that should be part of the
     *                       event.
     *
     * @return ConstraintFilter  A reference to $this to allow
     *                                      method chaining.
     */
    public function addRequiredField($field): self
    {
        return $this->addConstraint($field, new Horde_Constraint_Not(new Horde_Constraint_Null()));
    }

    /**
     * Adds all arguments passed as required fields.
     *
     * @return ConstraintFilter  A reference to $this to allow
     *                                      method chaining.
     */
    public function addRequiredFields(): self
    {
        foreach (func_get_args() as $f) {
            $this->addRequiredField($f);
        }

        return $this;
    }

    /**
     * Returns Horde_Log_Filter::ACCEPT to accept the message,
     * Horde_Log_Filter::IGNORE to ignore it.
     *
     * @param LogMessage $event  Log event.
     *
     * @return bool  accepted?
     */
    public function accept(LogMessage $event): bool
    {
        $eventArr = array_merge(['message' => $event->message(), 'loglevel' => $event->level()->criticality()], $event->context());
        foreach ($this->constraints as $field => $constraint) {
            $value = $eventArr[$field] ?? null;
            if (!$constraint->evaluate($value)) {
                return LogFilter::IGNORE;
            }
        }

        return LogFilter::ACCEPT;
    }
}
