# Bootstrap Error Logging

Resilient logging for early bootstrap errors before application-level
logger configuration is available.

## Setup

Two independent loggers:

**Logger 1 -- system log** (`SystemdJournalHandler` with syslog fallback):
journal available = thin message + structured fields;
journal unavailable = fat message with JSON context via syslog.

**Logger 2 -- STDERR** (`StreamHandler`): always active, fat message.

## Run the example

```bash
cd ~/php/git/horde/Log
php doc/examples/bootstrap_error_logging.php
```

## Verify journal output (native structured fields)

```bash
journalctl SYSLOG_IDENTIFIER=horde-bootstrap -o verbose --since "1 min ago"
```

Query by individual structured field:

```bash
journalctl EXCEPTION_CLASS=RuntimeException --since "1 min ago"
journalctl HORDE_PHASE=bootstrap --since "1 min ago"
```

JSON output for machine parsing:

```bash
journalctl SYSLOG_IDENTIFIER=horde-bootstrap -o json-pretty --since "1 min ago"
```

## Verify syslog fallback

Force fallback by pointing to a nonexistent socket:

```bash
php -r '
require "vendor/autoload.php";
use Horde\Log\Handler\SystemdJournalHandler;
use Horde\Log\Handler\SystemdJournalOptions;
use Horde\Log\Formatter\{Psr3Formatter, SimpleFormatter, JsonContextFormatter};
use Horde\Log\Logger;

$opts = new SystemdJournalOptions();
$opts->socketPath = "/tmp/no-such-socket";
$opts->syslogFallback = true;
$opts->ident = "horde-fallback-test";
$opts->fallbackFormatters = [
    new Psr3Formatter(),
    new SimpleFormatter("%message%"),
    new JsonContextFormatter(),
];
$logger = new Logger([new SystemdJournalHandler($opts)]);
$logger->error("Fallback test", ["key" => "value"]);
'

journalctl SYSLOG_IDENTIFIER=horde-fallback-test -o verbose --since "1 min ago"
```

## Syslog caveat

Do **not** include `PHP_EOL` in `SimpleFormatter` format strings for syslog
targets. `syslog()` treats newlines as message separators, which produces
a spurious empty second entry.

```php
// STDERR / file streams — needs PHP_EOL
new SimpleFormatter('%message%' . PHP_EOL)

// syslog fallback — omit PHP_EOL
new SimpleFormatter('%message%')
```
