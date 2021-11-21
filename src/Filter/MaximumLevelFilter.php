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
 * @subpackage Filters
 */
declare(strict_types=1);
namespace Horde\Log\Filter;
use Horde\Log\LogFilter;
use Horde\Log\LogMessage;
use InvalidArgumentException;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Filters
 */
class MaximumLevelFilter implements LogFilter
{
    /**
     * Filter level.
     *
     * @var int
     */
    protected int $level;

    /**
     * Filter out any log messages greater than $level.
     *
     * @param integer $level  Maximum log level to pass through the filter.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(int $level)
    {
        if (!is_integer($level)) {
            throw new InvalidArgumentException('Level must be an integer');
        }

        $this->level = $level;
    }

    /**
     * Returns Horde_Log_Filter::ACCEPT to accept the message,
     * Horde_Log_Filter::IGNORE to ignore it.
     *
     * @param LogMessage $event  Log event.
     *
     * @return bool  Accepted?
     */
    public function accept(LogMessage $event): bool
    {
        return ($event->level()->criticality() <= $this->level);
    }

}
