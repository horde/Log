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
 * @subpackage Formatters
 */
declare(strict_types=1);

namespace Horde\Log\Formatter;

use Horde\Log\LogFormatter;
use Horde\Log\LogMessage;
use InvalidArgumentException;
use Stringable;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Formatters
 */
class Psr3Formatter implements LogFormatter
{
    /**
     * Hash of default context values.
     *
     * @var string[]|Stringable[]|int[]|float[]
     */
    private array $defaultContext;
    /**
     * Constructor.
     *
     * @param string[]|Stringable[]|int[]|float[] $defaultContext Defaults hash for missing context values. Key will be the placeholder, value will be the filled in data. Actual context overwrites defaults
     *
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $defaultContext = [])
    {
        $this->defaultContext = $defaultContext;
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
        $context = array_merge($this->defaultContext, $event->context());
        $placeholders = [];
        foreach ($context as $key => $value) {
            // Filter out incompatible objects, arrays etc
            if ($value instanceof Stringable || is_string($value) || is_numeric($value)) {
                $placeholders['{' . $key . '}'] = (string) $value;
            }
        }
        return strtr($event->formattedMessage(), $placeholders);
    }
}
