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
use Throwable;

/**
 * Appends JSON-encoded context to the formatted message.
 *
 * Designed for log targets that have no native structured-data support
 * (syslog, file streams, STDERR). The formatter serializes the LogMessage
 * context array as JSON and appends it to the already-formatted message,
 * separated by " | ".
 *
 * Exception objects in context['exception'] are converted to a serializable
 * representation. Non-serializable values (resources, closures) are filtered.
 * The 'timestamp' key is omitted since it is typically already present in the
 * formatted message prefix.
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Formatters
 */
class JsonContextFormatter implements LogFormatter
{
    /**
     * Separator between the formatted message and the JSON context.
     */
    private string $separator;

    /**
     * Context keys to exclude from JSON output.
     *
     * @var string[]
     */
    private array $excludeKeys;

    /**
     * JSON encoding flags.
     */
    private int $jsonFlags;

    /**
     * Constructor.
     *
     * @param string   $separator   Separator between message and JSON context
     * @param string[] $excludeKeys Context keys to exclude from JSON output
     * @param int      $jsonFlags   Flags passed to json_encode()
     */
    public function __construct(
        string $separator = ' | ',
        array $excludeKeys = ['timestamp'],
        int $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    ) {
        $this->separator = $separator;
        $this->excludeKeys = $excludeKeys;
        $this->jsonFlags = $jsonFlags;
    }

    /**
     * Format a log message by appending JSON-encoded context.
     *
     * @param LogMessage $event Log event
     *
     * @return string Formatted message with JSON context appended
     */
    public function format(LogMessage $event): string
    {
        $message = $event->formattedMessage();
        $context = $event->context();

        // Remove excluded keys
        foreach ($this->excludeKeys as $key) {
            unset($context[$key]);
        }

        if ($context === []) {
            return $message;
        }

        $serializable = $this->makeSerializable($context);
        $json = json_encode($serializable, $this->jsonFlags);

        if ($json === false) {
            $json = '{"_json_error":"' . json_last_error_msg() . '"}';
        }

        // Strip trailing newline from message before appending JSON,
        // then restore it after
        $trailingNewline = '';
        if (str_ends_with($message, PHP_EOL)) {
            $trailingNewline = PHP_EOL;
            $message = substr($message, 0, -strlen(PHP_EOL));
        }

        return $message . $this->separator . $json . $trailingNewline;
    }

    /**
     * Convert context values to a JSON-serializable form.
     *
     * @param mixed[] $context Context array
     *
     * @return mixed[] Serializable context
     */
    private function makeSerializable(array $context): array
    {
        $result = [];

        foreach ($context as $key => $value) {
            if ($key === 'exception' && $value instanceof Throwable) {
                $result[$key] = $this->serializeException($value);
                continue;
            }

            if ($value instanceof Throwable) {
                $result[$key] = $this->serializeException($value);
                continue;
            }

            if (is_resource($value) || $value instanceof Closure) {
                continue;
            }

            if (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $result[$key] = (string) $value;
                    continue;
                }
                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->makeSerializable($value);
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Serialize a Throwable to an associative array.
     *
     * @param Throwable $exception Exception to serialize
     *
     * @return array<string, mixed> Serialized exception data
     */
    private function serializeException(Throwable $exception): array
    {
        $data = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        if ($exception->getPrevious() !== null) {
            $data['previous'] = $this->serializeException($exception->getPrevious());
        }

        return $data;
    }
}
