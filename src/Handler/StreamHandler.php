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

use Horde\Log\Filter;
use Horde\Log\Formatter\SimpleFormatter;
use Horde\Log\LogFormatter;
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
class StreamHandler extends BaseHandler
{
    use SetOptionsTrait;
    private Options $options;

    /**
     * Holds the PHP stream to log to.
     *
     * @var null|resource
     */
    protected $stream = null;

    /**
     * The open mode.
     *
     * @var string
     */
    protected string $mode;

    /**
     * The stream uri to open.
     *
     * @var string
     */
    protected $streamOrUrl;

    /**
     * Class Constructor
     *
     * @param mixed $streamOrUrl              Stream or URL to open as a
     *                                        stream.
     * @param string $mode                    Mode, only applicable if a URL
     *                                        is given.
     * @param LogFormatter[]|null $formatters  Log formatter.
     *
     * @throws LogException
     */
    public function __construct(
        $streamOrUrl,
        $mode = 'a+',
        Options $options = null,
        array $formatters = null
    )
    {
        $this->options = $options ?? new Options();
        $this->formatters = $formatters ?? [new SimpleFormatter()];
        $this->mode = $mode;
        $this->streamOrUrl = $streamOrUrl;

        if (is_resource($streamOrUrl)) {
            if (get_resource_type($streamOrUrl) != 'stream') {
                throw new LogException(__CLASS__ . ': Resource is not a stream');
            }

            if ($mode && $mode != 'a+') {
                throw new LogException(__CLASS__ . ': Mode cannot be changed on existing streams');
            }

            $this->stream = $streamOrUrl;
        } else {
            $this->__wakeup();
        }
    }

    /**
     * Wakup function - reattaches stream.
     *
     * @throws LogException
     */
    public function __wakeup()
    {
        if (!($stream = @fopen($this->streamOrUrl, $this->mode, false))) {
            throw new LogException(__CLASS__ . ': "' . $this->streamOrUrl . '" cannot be opened with mode "' . $this->mode . '"');
        }
        $this->stream = $stream;
    }

    /**
     * Write a message to the log.
     *
     * @param LogMessage $event  Log event.
     *
     * @return bool  True.
     * @throws LogException
     */
    public function write(LogMessage $event): bool
    {
        $message = $event->formattedMessage();
        if (!empty($this->options->ident)) {
            $message = $this->options->ident . ' ' . $message;
        }
        if (!is_resource($this->stream)) {
            throw new LogException(__CLASS__ . ': Unable to write, no stream opened');
        }
        if (!@fwrite($this->stream, $message)) {
            throw new LogException(__CLASS__ . ': Unable to write to stream');
        }

        return true;
    }
}
