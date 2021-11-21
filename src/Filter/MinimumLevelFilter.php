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
use Psr\Log\InvalidArgumentException;
/**
 * @author     Ralf Lang <lang@b1-systems.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Filters
 * @since      v3.0.0
 */
class MinimumLevelFilter implements LogFilter
{
    /**
     * Filter level.
     *
     * @var integer
     */
    protected int $level;

    /**
     * Filter out any log messages more urgent than $level.
     *
     * @param int $level  Maximum log level to pass through the filter.
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
     * Returns Horde\Log\Filter::ACCEPT to accept the message,
     * Horde\Log\Filter::IGNORE to ignore it.
     *
     * @param LogMessage $event  Log event.
     *
     * @return bool  Accepted?
     */
    public function accept(LogMessage $event): bool
    {
        return ($event->level()->criticality() >= $this->level);
    }

}
