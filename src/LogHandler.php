<?php
/**
 * Horde Log package.
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @category Horde
 * @package  Log
 * @author   Ralf Lang <lang@b1-systems.de>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
declare(strict_types=1);

namespace Horde\Log;

use Horde\Util\HordeString;

/**
 * interface of a Log Handler.
 *
 * @category Horde
 * @package  Log
 * @author   Ralf Lang <lang@b1-systems.de>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
interface LogHandler
{
    /**
     * Add a filter specific to this handler.
     *
     * Handlers cannot undo the filtering at logger level
     *
     * @param LogFilter $filter  Filter to add.
     */
    public function addFilter(LogFilter $filter): void;

    /**
     * Log a message to this handler.
     *
     * Check all filters and expand it before delegating to the write method
     *
     * @param LogMessage $event  Log event.
     */
    public function log(LogMessage $event): void;

    /**
     * Sets an option specific to the implementation of the log handler.
     *
     * @param string $optionKey   Key name for the option to be changed.  Keys
     *                            are handler-specific.
     * @param mixed $optionValue  New value to assign to the option
     *
     * @return bool  True.
     * @throws LogException
     */
    public function setOption($optionKey, $optionValue): bool;

    /**
     * Buffer a message to be stored in the storage.
     *
     * @param LogMessage $event  Log event.
     */
    public function write(LogMessage $event): bool;
}
