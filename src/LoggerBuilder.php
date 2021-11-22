<?php
declare(strict_types=1);
namespace Horde\Log;
/**
 * Iteratively configure a logger
 * 
 * Use this to allow creating complex logger setups
 * from a config file or similar source.
 * 
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Log
 */
class LoggerBuilder
{
    private LogLevels $loglevels;
    private Logger $logger;

    public function __construct(LogLevels $loglevels = null)
    {
        $this->reset($loglevels);
    }
    /**
     * Create an all-new logger instance without any handler or filter
     * 
     */
    public function reset(LogLevels $loglevels = null): self
    {
        $this->loglevels = $loglevels ?? LogLevels::initWithCanonicalLevels();
        $this->logger = new Logger([], $loglevels);
        return $this;
    }

    /**
     * Create a custom log level for the current logger
     *
     * @param integer $criticality
     * @param string $name
     * @return self
     */
    public function withLogLevel(int $criticality, string $name): self
    {
        $level = new LogLevel($criticality, $name);
        // The logger and the builder share the reference to the same object
        // No need for an actual injection into the logger
        $this->loglevels->register($level);
        return $this;
    }

    /**
     * Add a log handler to a logger
     *
     * @param LogHandler $handler
     * @return self
     */
    public function withLogHandler(LogHandler $handler): self
    {
        $this->logger->addHandler($handler);
        return $this;
    }

    /**
     * Return a logger
     * 
     * Resets the builder to default state
     *
     * @return Logger
     */
    public function build(): Logger
    {
        $logger = $this->logger;
        $this->reset();
        return $logger;
    }

    /**
     * Add a filter
     *
     * @param LogFilter $filter
     * @return self
     */
    public function withGlobalFilter(LogFilter $filter): self
    {
        return $this;
    }

}