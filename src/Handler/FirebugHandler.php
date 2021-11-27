<?php
/**
 * Horde Log package
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
use Horde\Log\LogFormatter;
use Horde\Log\LogHandler;
use Horde\Log\LogMessage;
use Horde\Log\LogException;
use Horde\Log\Formatter\SimpleFormatter;
use Horde_Log;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
class FirebugHandler extends BaseHandler
{
    use SetOptionsTrait;
    private FirebugOptions $options;
    /**
     * Array of buffered output.
     *
     * @var array[]
     */
    protected $buffer = [];

    /**
     * Mapping of log priorities to Firebug methods.
     *
     * @var string[]
     */
    protected static $methods = [
        Horde_Log::EMERG   => 'error',
        Horde_Log::ALERT   => 'error',
        Horde_Log::CRIT    => 'error',
        Horde_Log::ERR     => 'error',
        Horde_Log::WARN    => 'warn',
        Horde_Log::NOTICE  => 'info',
        Horde_Log::INFO    => 'info',
        Horde_Log::DEBUG   => 'debug',
    ];

    /**
     * Class Constructor
     *
     * @param LogFormatter[] $formatters  Log formatter.
     */
    public function __construct(FirebugOptions $options, array $formatters = null)
    {
        $this->options = $options ?? new FirebugOptions();
        $this->formatters = is_null($formatters)
            ? [new SimpleFormatter()]
            : $formatters;
    }

    /**
     * Write a message to the firebug console.  This function really just
     * writes the message to the buffer.  If buffering is enabled, the
     * message won't be output until the buffer is flushed. If
     * buffering is not enabled, the buffer will be flushed
     * immediately.
     *
     * @param LogMessage $event  Log event.
     *
     * @return bool  True.
     */
    public function write(LogMessage $event): bool
    {
        $message = $event->formattedMessage();
        if (!empty($this->options->ident)) {
            $message = $this->options->ident . ' ' . $message;
        }

        $this->buffer[] = ['message' => $message, 'level' => $event->level()->criticality()];

        if (empty($this->options->buffering)) {
            $this->flush();
        }

        return true;
    }

    /**
     * Flush the buffer.
     */
    public function flush(): bool
    {
        if (!count($this->buffer)) {
            return true;
        }

        $output = [];
        foreach ($this->buffer as $event) {
            $line = trim($event['message']);

            // Normalize line breaks.
            $line = str_replace("\r\n", "\n", $line);

            // Escape line breaks
            $line = str_replace("\n", "\\n\\\n", $line);

            // Escape quotes.
            $line = str_replace('"', '\\"', $line);

            // Firebug call.
            $method = self::$methods[$event['level']]
                ?? 'log';
            $output[] = 'console.' . $method . '("' . $line . '");';
        }

        echo '<script type="text/javascript">'
            . "\nif (('console' in window) || ('firebug' in console)) {\n"
            . implode("\n", $output) . "\n"
            . "}\n"
            . "</script>\n";

        $this->buffer = [];
        return true;
    }
}
