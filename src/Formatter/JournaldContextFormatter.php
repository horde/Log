<?php

/**
 * Horde Log package
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Formatters
 */

declare(strict_types=1);

namespace Horde\Log\Formatter;

use Closure;
use Horde\Log\LogFormatter;
use Horde\Log\LogMessage;
use Stringable;

/**
 * Formats a log message as systemd journal KEY=value fields.
 *
 * Produces newline-delimited KEY=value pairs suitable for consumption
 * by the SystemdJournalHandler or any target that speaks the native
 * journal protocol.
 *
 * - The formatted message is written to the MESSAGE= field.
 *   If the message string is empty and the context already contains
 *   a 'MESSAGE' key, the existing context value is preserved.
 * - The log level name is written to the LEVEL= field.
 * - All remaining context keys are uppercased.
 * - Values that are strings, numeric, or Stringable are cast to string.
 * - Values that are not stringable (arrays, resources, closures,
 *   non-Stringable objects) are silently dropped.
 *
 * PSR-3 placeholder interpolation is NOT performed by this formatter.
 * Prepend a Psr3Formatter in the formatter chain if placeholders are
 * needed.
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Formatters
 */
class JournaldContextFormatter implements LogFormatter
{
    /**
     * Context keys to exclude from the field output.
     *
     * These keys are handled specially (MESSAGE, LEVEL) or are
     * not useful in journal fields (timestamp).
     *
     * @var string[]
     */
    private array $excludeKeys;

    /**
     * Constructor.
     *
     * @param string[] $excludeKeys Context keys to exclude from field output
     */
    public function __construct(
        array $excludeKeys = ['timestamp'],
    ) {
        $this->excludeKeys = $excludeKeys;
    }

    /**
     * Format a log message as journald KEY=value fields.
     *
     * @param LogMessage $event Log event
     *
     * @return string Newline-delimited KEY=value pairs
     */
    public function format(LogMessage $event): string
    {
        $context = $event->context();
        $message = $event->formattedMessage();
        $fields = [];

        // MESSAGE field: prefer context MESSAGE when log message is empty
        if ($message === '' && isset($context['MESSAGE']) && $this->isStringable($context['MESSAGE'])) {
            $fields[] = 'MESSAGE=' . (string) $context['MESSAGE'];
        } else {
            $fields[] = 'MESSAGE=' . $message;
        }

        // LEVEL field from the log level name
        $fields[] = 'LEVEL=' . $event->level()->name();

        // Remove excluded keys and keys we handle specially
        foreach ($this->excludeKeys as $key) {
            unset($context[$key]);
        }
        unset($context['MESSAGE'], $context['message']);

        // Remaining context as uppercased fields
        foreach ($context as $key => $value) {
            if (!$this->isStringable($value)) {
                continue;
            }
            $fields[] = strtoupper((string) $key) . '=' . (string) $value;
        }

        return implode("\n", $fields) . "\n";
    }

    /**
     * Check whether a value can be cast to string.
     *
     * @param mixed $value The value to check
     *
     * @return bool True if the value is a string, numeric, or Stringable
     */
    private function isStringable(mixed $value): bool
    {
        if (is_string($value) || is_numeric($value)) {
            return true;
        }

        if ($value instanceof Stringable) {
            return true;
        }

        return false;
    }
}
