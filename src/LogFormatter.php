<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Log
 */
declare(strict_types=1);
namespace Horde\Log;
/**
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Log
 */
interface LogFormatter
{
    /**
     * Formats an event to be written by the handler.
     *
     * @param LogMessage $event  Log event.
     *
     * @return string  Formatted line.
     */
    public function format(LogMessage $event): string;

}
