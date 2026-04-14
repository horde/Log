<?php

/**
 * Bootstrap Error Logging Example
 *
 * Demonstrates resilient logging during early application bootstrap when
 * no application-level logger configuration is available yet.
 *
 * Scenario: Horde\Injector throws NotFoundException because it cannot bind
 * an interface (no factory registered) during DI autowiring of a controller.
 *
 * Logging strategy:
 *   Logger 1 — "system log": SystemdJournalHandler with syslog fallback
 *     - Journal available: thin structured message + rich context as fields
 *     - Journal unavailable: fat message with JSON context via syslog
 *   Logger 2 — "operator log": StreamHandler to STDERR
 *     - Always active, fat message with JSON context
 *
 * Usage:
 *   php doc/examples/bootstrap_error_logging.php
 *
 * To verify journal output (if systemd is available):
 *   journalctl SYSLOG_IDENTIFIER=horde-bootstrap -o verbose --since "1 min ago"
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 * License: BSD - See LICENSE file
 */

declare(strict_types=1);

// Autoloader — adjust path as needed
$candidates = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../../autoload.php',
];
foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        require_once $candidate;
        break;
    }
}

use Horde\Log\Formatter\JsonContextFormatter;
use Horde\Log\Formatter\Psr3Formatter;
use Horde\Log\Formatter\SimpleFormatter;
use Horde\Log\Handler\StreamHandler;
use Horde\Log\Handler\SystemdJournalHandler;
use Horde\Log\Handler\SystemdJournalOptions;
use Horde\Log\Logger;

// -----------------------------------------------------------------------
// 1. Simulate the exception that occurs during DI autowiring
// -----------------------------------------------------------------------

$rootCause = new RuntimeException(
    'Cannot bind interface or abstract class "Psr\Log\LoggerInterface" '
    . 'without a factory or implementation binding'
);
$midLevel = new RuntimeException(
    'Method __construct() of App\Controller\TaskController has unfulfilled '
    . 'dependencies (Parameter #0 [ <required> Psr\Log\LoggerInterface $logger ])',
    0,
    $rootCause,
);
$exception = new RuntimeException(
    'The requested interface was not found: App\Controller\TaskController',
    0,
    $midLevel,
);

// -----------------------------------------------------------------------
// 2. Set up Logger 1: System log (journal → syslog fallback)
// -----------------------------------------------------------------------

$journalOptions = new SystemdJournalOptions();
$journalOptions->ident = 'horde-bootstrap';
$journalOptions->syslogFallback = true;
$journalOptions->additionalFields = [
    'HORDE_VERSION' => '6.0',
    'HORDE_PHASE' => 'bootstrap',
];
// Formatters for the syslog fallback path (journal uses its own protocol).
// Note: no PHP_EOL — syslog() adds its own line termination and newlines
// in the message string cause duplicate journal entries.
$journalOptions->fallbackFormatters = [
    new Psr3Formatter(),
    new SimpleFormatter('%message%'),
    new JsonContextFormatter(),
];

$journalHandler = new SystemdJournalHandler($journalOptions);
$systemLogger = new Logger([$journalHandler]);

// -----------------------------------------------------------------------
// 3. Set up Logger 2: STDERR stream (always active)
// -----------------------------------------------------------------------

$stderrHandler = new StreamHandler(
    streamOrUrl: STDERR,
    mode: 'a+',
    options: null,
    formatters: [
        new Psr3Formatter(),
        new SimpleFormatter('%message%' . PHP_EOL),
        new JsonContextFormatter(),
    ],
);
$stderrLogger = new Logger([$stderrHandler]);

// -----------------------------------------------------------------------
// 4. Log the bootstrap error to both loggers
// -----------------------------------------------------------------------

$message = 'DI autowiring failed for {requested_class}';
$context = [
    'exception' => $exception,
    'requested_class' => 'App\Controller\TaskController',
    'bootstrap_phase' => 'controller_resolution',
    'root_cause' => $rootCause->getMessage(),
];

$systemLogger->error($message, $context);
$stderrLogger->error($message, $context);

// -----------------------------------------------------------------------
// 5. Output verification hints
// -----------------------------------------------------------------------

fwrite(STDERR, PHP_EOL);
fwrite(STDERR, "--- Bootstrap error logging example complete ---" . PHP_EOL);

if ($journalHandler->isAvailable()) {
    fwrite(STDERR, "Journal IS available. Check with:" . PHP_EOL);
    fwrite(STDERR, "  journalctl SYSLOG_IDENTIFIER=horde-bootstrap -o verbose --since '1 min ago'" . PHP_EOL);
} else {
    fwrite(STDERR, "Journal NOT available — fell back to syslog." . PHP_EOL);
    fwrite(STDERR, "Check syslog output (e.g., /var/log/syslog or journalctl if forwarded)." . PHP_EOL);
}
