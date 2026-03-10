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
}
