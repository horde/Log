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
namespace Horde\Log;
/**
 * @category   Horde
 * @subpackage Filters
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Filters
 */
interface LogFilter
{
    /**
     * Accept a message
     */
    const ACCEPT = true;

    /**
     * Filter out a message
     */
    const IGNORE = false;

    /**
     * Returns Horde_Log_Filter::ACCEPT to accept the message,
     * Horde_Log_Filter::IGNORE to ignore it.
     *
     * @param LogMessage $event  Log event.
     *
     * @return bool  Accepted?
     */
    public function accept(LogMessage $event): bool;

}
