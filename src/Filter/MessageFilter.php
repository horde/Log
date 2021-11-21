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
use InvalidArgumentException;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Filters
 */
class MessageFilter implements LogFilter
{
    /**
     * Filter regex.
     *
     * @var string
     */
    protected string $regexp;

    /**
     * Filter out any log messages not matching $regexp.
     *
     * @param string $regexp  Regular expression to test the log message.
     *
     * @throws InvalidArgumentException  Invalid regular expression.
     */
    public function __construct(string $regexp)
    {
        if (@preg_match($regexp, '') === false) {
            throw new InvalidArgumentException('Invalid regular expression ' . $regexp);
        }

        $this->regexp = $regexp;
    }

    /**
     * Returns Horde_Log_Filter::ACCEPT to accept the message,
     * Horde_Log_Filter::IGNORE to ignore it.
     *
     * @param LogMessage $event  Log event.
     *
     * @return bool  Accepted?
     */
    public function accept(LogMessage $event): bool
    {
        return (preg_match($this->regexp, $event->message()) > 0);
    }
}
