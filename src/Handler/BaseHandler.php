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
 * @subpackage Handlers
 */
declare(strict_types=1);

namespace Horde\Log\Handler;

use Horde\Log\LogFilter;
use Horde\Log\LogHandler;
use Horde\Log\LogFormatter;
use Horde\Log\LogMessage;
use Horde\Log\LogException;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
abstract class BaseHandler implements LogHandler
{
    /**
     * List of filters relevant only to this handler.
     *
     * @var LogFilter[]
     */
    protected $filters = [];

    /**
     * Formatters for this handler
     *
     * @var LogFormatter[]
     */
    protected $formatters = [];

    /**
     * Add a filter specific to this handler.
     *
     * Handlers cannot undo the filtering at logger level
     *
     * @param LogFilter $filter  Filter to add.
     */
    public function addFilter(LogFilter $filter): void
    {
        $this->filters[] = $filter;
    }

    /**
     * Log a message to this handler.
     *
     * Check all filters and expand it before delegating to the write method
     *
     * @param LogMessage $event  Log event.
     */
    public function log(LogMessage $event): void
    {
        // If any local filter rejects the message, don't log it.
        foreach ($this->filters as $filter) {
            if (!$filter->accept($event)) {
                return;
            }
        }
        $event->formatMessage($this->formatters);
        $this->write($event);
    }

    /**
     * Buffer a message to be stored in the storage.
     *
     * @param LogMessage $event  Log event.
     */
    abstract public function write(LogMessage $event): bool;
}
