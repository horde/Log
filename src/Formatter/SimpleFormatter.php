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
 * @subpackage Formatters
 */
declare(strict_types=1);
namespace Horde\Log\Formatter;
use Horde\Log\LogFormatter;
use Horde\Log\LogMessage;
use InvalidArgumentException;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Formatters
 */
class SimpleFormatter implements LogFormatter
{
    /**
     * Format string.
     *
     * @var string
     */
    protected $format;

    /**
     * Constructor.
     *
     * @param string[] $options  Configuration options:
     * <pre>
     * 'format' - (string) The log template.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct($options = null)
    {
        $format = (is_array($options) && isset($options['format']))
            ? $options['format']
            : $options;

        if (is_null($format)) {
            $format = '%timestamp% %levelName%: %message%' . PHP_EOL;
        }

        if (!is_string($format)) {
            throw new InvalidArgumentException('Format must be a string');
        }

        $this->format = $format;
    }

    /**
     * Formats an event to be written by the handler.
     *
     * @param LogMessage $event  Log event.
     *
     * @return string  Formatted line.
     */
    public function format(LogMessage $event): string
    {
        $output = $this->format;
        $context = $event->context();
        $context['message'] = $event->formattedMessage();
        foreach ($context as $name => $value) {
            $output = str_replace("%$name%", $value, $output);
        }
        return $output;
    }
}
