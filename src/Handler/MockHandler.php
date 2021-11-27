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
class MockHandler extends BaseHandler
{
    use SetOptionsTrait;
    private Options $options;

    public function __construct(Options $options = null)
    {
        $this->options = $options ?? new Options();
    }
    /**
     * Log events.
     *
     * @var LogMessage[]
     */
    public $events = [];

    /**
     * Was shutdown called?
     *
     * @var bool
     */
    public $shutdown = false;

    /**
     * Write a message to the log.
     *
     * @param LogMessage $event  Event data.
     */
    public function write(LogMessage $event): bool
    {
        $this->events[] = $event;
        return true;
    }

    /**
     * Record shutdown
     */
    public function shutdown(): void
    {
        $this->shutdown = true;
    }
}
