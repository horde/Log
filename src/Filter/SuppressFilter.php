<?php
/**
 * Horde Log package.
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

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Filters
 */
class SuppressFilter implements LogFilter
{
    /**
     * Accept all events?
     *
     * @var bool
     */
    protected bool $accept = LogFilter::ACCEPT;

    /**
     * This is a simple boolean filter.
     *
     * @param bool $suppress  Should all log events be suppressed?
     */
    public function suppress($suppress): bool
    {
        $this->accept = !$suppress;
        return $suppress;
    }

    /**
     * Decide if we accept messages.
     *
     * Returns Horde\Log\Filter::ACCEPT to accept the message,
     * Horde_Log_Filter::IGNORE to ignore it.
     *
     * @param LogMessage $event  Event data.
     *
     * @return bool  Accepted?
     */
    public function accept(LogMessage $event): bool
    {
        return $this->accept;
    }
}
