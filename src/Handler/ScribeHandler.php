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

use Horde\Log\Filter;
use Horde\Log\Formatter\SimpleFormatter;
use Horde\Log\LogHandler;
use Horde\Log\LogMessage;
use Horde\Log\LogException;
use Horde\Log\LogFormatter;
use Horde_Scribe_Client;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
class ScribeHandler extends BaseHandler
{
    use SetOptionsTrait;
    private ScribeOptions $options;
    /**
     * Scribe client.
     *
     * @var Horde_Scribe_Client
     */
    protected $scribe;

    /**
     * Constructor.
     *
     * @param Horde_Scribe_Client $scribe     Scribe client.
     * @param LogFormatter[] $formatters  Log formatter.
     */
    public function __construct(
        Horde_Scribe_Client $scribe,
        array $formatters = null,
        ScribeOptions $options = null
    )
    {
        $this->formatters = is_null($formatters)
            ? [new SimpleFormatter()]
            : $formatters;
        $this->scribe = $scribe;
        $this->options = $options ?? new ScribeOptions();
    }

    /**
     * Write a message to the log.
     *
     * @param LogMessage $event  Log event.
     *
     * @return bool  True.
     */
    public function write(LogMessage $event): bool
    {
        $context = $event->context();
        $message = $event->formattedMessage();

        if (!empty($this->options->ident)) {
            $message = $this->options->ident . ' ' . $message;
        }

        $category = $context['category']
            ?? $this->options->category;

        if (!$this->options->addNewline) {
            $message = rtrim($message);
        }
        $this->scribe->log($category, $message);
        return true;
    }
}
