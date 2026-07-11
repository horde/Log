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
use Throwable;

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
     * The template's `%name%` placeholders are looked up in the log
     * event's context (with the record-level fields — `timestamp`,
     * `level`, `levelName`, `message` — overlaid). Values not
     * referenced by the template are ignored: PSR-3 log calls
     * routinely carry structured context that a given template chooses
     * not to render, and touching those values here risks emitting
     * PHP warnings (Array-to-string) while the log line is being
     * written, which — depending on the error handler — can suppress
     * or corrupt the entry itself. Per PSR-3 "implementors MUST
     * ensure they treat context data with as much lenience as
     * possible."
     *
     * Referenced values are stringified with a light-touch strategy:
     *
     * - `exception` context key holding a `Throwable`: rendered as
     *   `Class(code): message at file:line` — PSR-3 reserves this
     *   key for `Throwable` instances and callers routinely reference
     *   `%exception%` in error templates.
     * - Scalars and `Stringable` objects: cast to string.
     * - Arrays: `json_encode()`; failure falls back to `[array]`.
     * - Resources / non-Stringable objects: rendered as
     *   `[resource(type)]` / `[object ClassName]` — safe types-only
     *   markers, matching what Symfony's HttpKernel Logger does.
     *
     * @param LogMessage $event  Log event.
     *
     * @return string  Formatted line.
     */
    public function format(LogMessage $event): string
    {
        $context = $event->context();
        $context['timestamp'] = $event->timestamp()->format('c');
        $context['level'] = $event->level()->criticality();
        $context['levelName'] = $event->level()->name();
        $context['message'] = $event->formattedMessage();

        $placeholders = self::extractPlaceholders($this->format);
        if ($placeholders === []) {
            return $this->format;
        }

        $replacements = [];
        foreach ($placeholders as $name) {
            if (!array_key_exists($name, $context)) {
                // Leave unreferenced (or unset-in-context) placeholders
                // literal in the output so a misconfigured template
                // surfaces the missing key rather than producing an
                // empty-looking log line.
                continue;
            }
            $replacements['%' . $name . '%'] = self::stringifyContextValue($name, $context[$name]);
        }

        return strtr($this->format, $replacements);
    }

    /**
     * Extract the set of `%name%` placeholder names from the template.
     *
     * @return list<string>
     */
    private static function extractPlaceholders(string $format): array
    {
        if (!preg_match_all('/%([A-Za-z_][A-Za-z0-9_]*)%/', $format, $matches)) {
            return [];
        }
        // Deduplicate so we do the stringify work once per placeholder
        // regardless of how many times it appears in the template.
        return array_values(array_unique($matches[1]));
    }

    /**
     * Reduce an arbitrary context value to a string safely.
     *
     * PSR-3 requires that context handling never raise a PHP notice,
     * warning or error. The strategy here mirrors {@see self::format()}'s
     * docblock.
     */
    private static function stringifyContextValue(string $name, mixed $value): string
    {
        // Reserved 'exception' key: PSR-3 mandates it hold a Throwable.
        // Callers may still pass something else (leniency), so only
        // apply the Throwable rendering when the value actually is one.
        if ($name === 'exception' && $value instanceof Throwable) {
            return sprintf(
                '%s(%s): %s at %s:%d',
                $value::class,
                (string) $value->getCode(),
                $value->getMessage(),
                $value->getFile(),
                $value->getLine(),
            );
        }

        if ($value === null) {
            return '';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        if ($value instanceof Stringable) {
            return (string) $value;
        }
        if (is_array($value)) {
            $encoded = json_encode($value);
            return $encoded === false ? '[array]' : $encoded;
        }
        if (is_object($value)) {
            return '[object ' . $value::class . ']';
        }
        if (is_resource($value)) {
            return '[resource(' . get_resource_type($value) . ')]';
        }
        return '[' . gettype($value) . ']';
    }
}
