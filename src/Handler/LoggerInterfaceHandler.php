<?php
/**
 * Wrap any PSR-3 logger as a Handler for the Horde Logger
 *
 * @author     Ralf Lang <lang@b1-systems.de>
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
use Horde\Log\LogLevel;
use Horde\Log\LogException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * @author     Ralf Lang <lang@b1-systems.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
final class LoggerInterfaceHandler implements LogHandler
{
    private LoggerInterface $logger;
    /**
     * List of filters relevant only to this handler.
     *
     * @var LogFilter[]
     */
    protected array $filters = [];

    /**
     * Formatters for this handler.
     *
     * @var LogFormatter[]
     */
    protected array $formatters = [];

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger The PSR-3 Logger to wrap
     * @param LogFilter[] $filters Any filters to run before logging
     * @param LogFormatter[] $formatters Any formatters to run before logging
     */
    public function __construct(LoggerInterface $logger, array $filters = [], array $formatters = [])
    {
        $this->logger = $logger;
        $this->filters = $filters;
        $this->formatters = $formatters;
    }
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
     * Sets an option specific to the implementation of the log handler.
     *
     * @param string $optionKey   Key name for the option to be changed.  Keys
     *                            are handler-specific.
     * @param mixed $optionValue  New value to assign to the option
     *
     * @return bool  True.
     * @throws LogException
     */
    public function setOption($optionKey, $optionValue): bool
    {
        return true;
    }

    /**
     * Buffer a message to be stored in the storage.
     *
     * @param LogMessage $event  Log event.
     */
    public function write(LogMessage $event): bool
    {
        try {
            $this->logger->log($event->level()->name(), $event->formattedMessage(), $event->context());
        } catch (InvalidArgumentException $e) {
            return false;
        }
        return true;
    }
}
