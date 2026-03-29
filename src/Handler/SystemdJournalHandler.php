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
 * @subpackage Handlers
 */

declare(strict_types=1);

namespace Horde\Log\Handler;

use Horde\Log\LogFilter;
use Horde\Log\LogFormatter;
use Horde\Log\LogHandler;
use Horde\Log\LogMessage;
use Horde\Log\LogException;

/**
 * SystemdJournalHandler - Write logs directly to systemd journal socket
 *
 * This handler writes directly to /run/systemd/journal/socket using the
 * native systemd journal protocol, which allows for structured logging
 * with custom metadata fields.
 *
 * Unlike syslog, this approach:
 * - Supports arbitrary custom fields (e.g., HORDE_COMPONENT, CODE_FILE)
 * - Allows rich structured logging that can be queried by field
 * - Bypasses the syslog compatibility layer
 * - Preserves data types and structure
 *
 * Example usage:
 * <code>
 * $options = new SystemdJournalOptions();
 * $options->ident = 'myapp';
 * $options->additionalFields = [
 *     'HORDE_VERSION' => '6.0',
 *     'ENVIRONMENT' => 'production'
 * ];
 *
 * $handler = new SystemdJournalHandler($options);
 * $logger->addHandler($handler);
 * </code>
 *
 * View logs with journalctl:
 * <code>
 * journalctl SYSLOG_IDENTIFIER=myapp -o verbose
 * journalctl HORDE_VERSION=6.0
 * </code>
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 * @see        https://www.freedesktop.org/software/systemd/man/systemd.journal-fields.html
 */
class SystemdJournalHandler extends BaseHandler
{
    use SetOptionsTrait;

    /**
     * Configuration options
     */
    protected SystemdJournalOptions $options;

    /**
     * Socket resource for journal communication
     *
     * @var resource|null
     */
    private $socket = null;

    /**
     * Class Constructor
     *
     * @param null|SystemdJournalOptions $options     Log options
     * @param LogFormatter[]             $formatters  Log formatters
     * @param LogFilter[]                $filters     Log filters
     */
    public function __construct(
        ?SystemdJournalOptions $options = null,
        array $formatters = [],
        array $filters = []
    ) {
        $this->options = $options ?? new SystemdJournalOptions();
        $this->formatters = $formatters;
        $this->filters = $filters;
    }

    /**
     * Check if systemd journal is available
     *
     * @return bool True if journal socket exists and is writable
     */
    public function isAvailable(): bool
    {
        return file_exists($this->options->socketPath)
            && is_writable($this->options->socketPath);
    }

    /**
     * Write a message to the systemd journal
     *
     * @param LogMessage $event  Log event
     *
     * @return bool  True on success
     * @throws LogException If journal is not available or write fails
     */
    public function write(LogMessage $event): bool
    {
        if (!$this->isAvailable()) {
            throw new LogException(
                'Systemd journal socket not available at ' . $this->options->socketPath
            );
        }

        // Lazy socket initialization
        if ($this->socket === null) {
            $this->socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
            if ($this->socket === false) {
                throw new LogException('Failed to create socket: ' . socket_strerror(socket_last_error()));
            }

            if (!socket_connect($this->socket, $this->options->socketPath)) {
                $error = socket_strerror(socket_last_error($this->socket));
                socket_close($this->socket);
                $this->socket = null;
                throw new LogException('Failed to connect to journal socket: ' . $error);
            }
        }

        // Build message payload in systemd journal format
        $payload = $this->buildPayload($event);

        $result = socket_send($this->socket, $payload, strlen($payload), 0);
        if ($result === false) {
            $error = socket_strerror(socket_last_error($this->socket));
            throw new LogException('Failed to write to journal: ' . $error);
        }

        return true;
    }

    /**
     * Build journal payload from log message
     *
     * Constructs newline-delimited key=value pairs according to systemd journal protocol
     *
     * @param LogMessage $event  Log event
     *
     * @return string  Journal payload
     */
    protected function buildPayload(LogMessage $event): string
    {
        $payload = '';

        // MESSAGE field (required)
        $payload .= 'MESSAGE=' . $event->formattedMessage() . "\n";

        // PRIORITY field (syslog priority)
        $payload .= 'PRIORITY=' . $event->level()->criticality() . "\n";

        // SYSLOG_IDENTIFIER field
        $payload .= 'SYSLOG_IDENTIFIER=' . $this->options->ident . "\n";

        // Add configured additional fields
        foreach ($this->options->additionalFields as $key => $value) {
            $sanitizedKey = $this->sanitizeFieldName($key);
            if ($sanitizedKey !== '' && $sanitizedKey[0] !== '_') {
                $payload .= $sanitizedKey . '=' . $value . "\n";
            }
        }

        // Add context fields from the log message
        foreach ($event->context() as $key => $value) {
            // Handle exception objects per PSR-3 specification
            if ($key === 'exception' && $value instanceof \Throwable) {
                // Extract exception metadata for journal fields
                $payload .= 'EXCEPTION_CLASS=' . get_class($value) . "\n";
                $payload .= 'EXCEPTION_MESSAGE=' . $value->getMessage() . "\n";
                $payload .= 'EXCEPTION_CODE=' . $value->getCode() . "\n";
                $payload .= 'EXCEPTION_FILE=' . $value->getFile() . "\n";
                $payload .= 'EXCEPTION_LINE=' . $value->getLine() . "\n";

                // Skip to next context item (exception object itself can't be serialized)
                continue;
            }

            // Convert context keys to journal field names
            $fieldName = $this->contextKeyToFieldName($key);

            // Only add primitive types (string, int, float, bool)
            if (is_scalar($value)) {
                $payload .= $fieldName . '=' . $value . "\n";
            }
        }

        return $payload;
    }

    /**
     * Convert context key to systemd journal field name
     *
     * Context keys are converted to uppercase and sanitized
     * Special keys like 'timestamp' map to standard journal fields
     *
     * @param string $key  Context key
     *
     * @return string  Journal field name
     */
    protected function contextKeyToFieldName(string $key): string
    {
        // Map special context keys to standard journal fields
        $mapping = [
            'timestamp' => 'SYSLOG_TIMESTAMP',
            'pid' => 'SYSLOG_PID',
            'file' => 'CODE_FILE',
            'line' => 'CODE_LINE',
            'function' => 'CODE_FUNC',
        ];

        if (isset($mapping[$key])) {
            return $mapping[$key];
        }

        // Convert to uppercase and sanitize
        return $this->sanitizeFieldName($key);
    }

    /**
     * Sanitize field name for systemd journal
     *
     * Field names must be uppercase alphanumeric + underscore
     * and cannot start with underscore (reserved for systemd)
     *
     * @param string $name  Field name
     *
     * @return string  Sanitized field name
     */
    protected function sanitizeFieldName(string $name): string
    {
        // Convert to uppercase
        $name = strtoupper($name);

        // Replace non-alphanumeric characters with underscore
        $name = preg_replace('/[^A-Z0-9_]/', '_', $name);

        // Remove leading underscores (reserved by systemd)
        $name = ltrim($name, '_');

        return $name;
    }

    /**
     * Destructor - close socket
     */
    public function __destruct()
    {
        if ($this->socket !== null) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }
}
