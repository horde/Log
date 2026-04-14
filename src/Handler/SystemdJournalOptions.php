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

use Horde\Log\LogFormatter;

/**
 * Configuration options for SystemdJournalHandler
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
class SystemdJournalOptions extends Options
{
    /**
     * SYSLOG_IDENTIFIER field value
     */
    public string $ident = 'horde';

    /**
     * Path to systemd journal socket
     */
    public string $socketPath = '/run/systemd/journal/socket';

    /**
     * Additional custom fields to include with every log message
     *
     * Keys should be uppercase field names (e.g., 'HORDE_COMPONENT')
     * Values should be strings
     *
     * @var array<string, string>
     */
    public array $additionalFields = [];

    /**
     * Fall back to syslog when journal socket is unavailable.
     *
     * When true, write() delegates to an internal SyslogHandler instead
     * of throwing LogException. This is useful for environments where
     * systemd journal may or may not be present (e.g., containers).
     */
    public bool $syslogFallback = false;

    /**
     * SyslogOptions for the fallback handler.
     *
     * If null, defaults are created using $ident as the syslog identifier.
     */
    public ?SyslogOptions $syslogOptions = null;

    /**
     * Formatters for the fallback SyslogHandler.
     *
     * Since syslog has no native structured-data support, the fallback
     * handler typically needs formatters that serialize context into the
     * message string (e.g., JsonContextFormatter).
     *
     * If empty, the fallback handler uses SyslogHandler's defaults.
     *
     * @var LogFormatter[]
     */
    public array $fallbackFormatters = [];
}
