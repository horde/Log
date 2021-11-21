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

use Horde\Util\HordeString;
use Psr\Log\InvalidArgumentException;
use Stringable;

/**
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Log
 *
 */
class Logger implements LoggerInterface
{
    /* Serialize version. */
    const VERSION = 1;

    /**
     * Log levels where the keys are the level priorities and the values are
     * the level names.
     *
     * @var LogLevels
     */
    protected LogLevels $levels;

    /**
     * Handler objects.
     *
     * @var LogHandler[]
     */
    protected $handlers = array();

    /**
     * Horde_Log_Filter objects.
     *
     * @var LogFilter[]
     */
    protected $filters = array();

    /**
     * Constructor.
     *
     * @param LogHandler[] $handlers The list of handlers. 
     *                  TODO: Is defaulting to the null handler any better than defaulting to no handler?
     * @param LogLevels|null $levels A list of log levels to operate on. Null initializes with the RFC loglevels
     * @param LogFilter[]  $filters A list of global filters to apply before any log handler. Log handlers may have their own filters
     */
    public function __construct(array $handlers = [], LogLevels $levels = null, array $filters = [])
    {
        if ($levels) {
            $this->levels = $levels;
        } else {
            $levels = LogLevels::initWithCanonicalLevels();
        }
        foreach ($handlers as $handler) {
            $this->addHandler($handler);
        }
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }
    }

    /**
     * Serialize.
     *
     * @return string  Serialized representation of this object.
     */
    public function serialize()
    {
        return serialize(array(
            self::VERSION,
            $this->filters,
            $this->handlers
        ));
    }

    /**
     * Unserialize.
     *
     * @param string $data  Serialized data.
     *
     * @throws LogException
     */
    public function unserialize($data): void
    {
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data[0]) ||
            ($data[0] != self::VERSION)) {
            throw new LogException('Cache version change');
        }

        $this->filters = $data[1];
        $this->handlers = $data[2];
    }

    /**
     * Undefined method handler allows a shortcut:
     * <pre>
     * $log->levelName('message');
     *   instead of
     * $log->log('message', Horde_Log_LEVELNAME);
     * </pre>
     *
     * @param string $method  Log level name.
     * @param string|object|stringable $params  Message to log.
     * @param array  $context The context for the message.
     */
/*    public function __call($method, $params)
    {
        TODO: Do we really want to support that mess?
        We already support the canonic names
    }*/

    /*
      The Logger methods.
      Compared to PSR-3 type hints, we explicitly hint against LogMessage to
      provide specific behavior. This is why we copy it rather than using the trait
     */
    /**
     * System is unusable.
     *
     * @param string|Stringable|LogMessage $message
     * @param mixed[]  $context
     *
     * @return void
     */
    public function emergency($message, array $context = array()): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|Stringable|LogMessage $message
     * @param mixed[]  $context
     *
     * @return void
     */
    public function alert($message, array $context = array()): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|Stringable|LogMessage $message
     * @param mixed[]  $context
     *
     * @return void
     */
    public function critical($message, array $context = array()): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable|LogMessage $message The message to submit
     * @param mixed[]  $context An array of context
     *
     * @return void
     */
    public function error($message, array $context = array()): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|Stringable|LogMessage $message
     * @param mixed[]  $context
     *
     * @return void
     */
    public function warning($message, array $context = array()): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string|Stringable|LogMessage $message
     * @param mixed[]  $context
     *
     * @return void
     */
    public function notice($message, array $context = array()): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|Stringable|LogMessage $message
     * @param mixed[]  $context
     *
     * @return void
     */
    public function info($message, array $context = array()): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param mixed[]  $context
     *
     * @return void
     */
    public function debug($message, array $context = array()): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
    /**
     * Logs with an arbitrary level.
     *
     * @param int|string|LogLevel  $level
     * @param string|Stringable|LogMessage $message
     * @param mixed[] $context User code supplied additional info
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = array()): void
    {
        $loglevel = null;
        // Error if the requested level is not present
        if ($level instanceof LogLevel) {
            $this->levels->getByCriticality($level->criticality());
            $this->levels->getByLevelName($level->name());
            $loglevel = $level;
        }
        elseif (is_int($level)) {
            $loglevel = $this->levels->getByCriticality($level);
        }
        elseif (is_string($level)) {
            $loglevel = $this->levels->getByLevelName($level);
        }
        if (is_null($loglevel)) {
            throw new InvalidArgumentException('Unsupported log level type, try string or numeric');
        }
        // Special handling of submitted native LogMessage objects
        if ($message instanceof LogMessage) {
            $logMessage = new LogMessage($loglevel, $message->message(), $message->context());
            $logMessage->mergeContext($context);
        } else {
            // The log message will auto-generate context[timestamp] unless missing
            $logMessage = new LogMessage($loglevel, (string) $message, $context);
        }

        // Apply any global prefilters, may reject the message
        foreach ($this->filters as $filter)
        {
            if (!$filter->accept($logMessage)) {
                return;
            }
        }

        // Delegate to all registered handlers
        foreach ($this->handlers as $handler)
        {
            // Any processing and interpolation is up to the log handler
            $handler->log($logMessage);
        }
    }

    /**
     * Add a filter that will be applied before all log handlers.
     * Before a message will be received by any of the handlers, it
     * must be accepted by all filters added with this method.
     *
     * @param LogFilter $filter  Filter to add.
     */
    public function addFilter(LogFilter $filter): void
    {
        $this->filters[] = $filter;
    }

    /**
     * Add a handler.  A handler is responsible for taking a log
     * message and writing it out to storage.
     *
     * @param LogHandler $handler  Handler to add.
     */
    public function addHandler(LogHandler $handler): void
    {
        $this->handlers[] = $handler;
    }
}
