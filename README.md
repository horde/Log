# Horde Log

PSR-3 compatible logging library with legacy API support.

## Overview

Horde Log provides two distinct APIs:

1. **PSR-3 Native Logger** (`src/`, namespace `Horde\Log`)
   - Modern PSR-4 autoloaded implementation
   - Implements `Psr\Log\LoggerInterface` with Horde extensions
   - Structured logging with context arrays
   - Recommended for new code
   - Native systemd journal backend allows filtering on context attributes

2. **Legacy Logger** (`lib/`, class prefix `Horde_Log_`)
   - PSR-0 autoloaded implementation
   - Original Horde logging API (based on Zend_Log)
   - Maintained for backward compatibility

Both implementations can interoperate:
- `Horde\Log\Handler\LoggerInterfaceHandler` wraps any PSR-3 logger as a Horde classic handler
- Applications can inject PSR-3 loggers while maintaining legacy Horde log infrastructure

## Installation

```bash
composer require horde/log
```

## Basic Usage

### PSR-3 Logger (Recommended)

```php
use Horde\Log\Logger;
use Horde\Log\Handler\StreamHandler;
use Horde\Log\LogLevel;

$logger = new Logger();
$logger->addHandler(new StreamHandler('/var/log/app.log'));

// Structured logging with context
$logger->info('User authenticated', [
    'username' => 'alice',
    'session_id' => 'abc123',
    'success' => true,
]);

$logger->error('Authentication failed', [
    'username' => 'bob',
    'reason' => 'invalid_password',
]);
```

### Legacy Logger

```php
$logger = new Horde_Log_Logger(
    new Horde_Log_Handler_Stream('/var/log/app.log')
);

$logger->info('User authenticated: alice');
$logger->err('Authentication failed for bob');
```

## Handlers

| Handler | Legacy (lib/) | Modern (src/) | Notes |
|---------|---------------|---------------|-------|
| Stream | `Horde_Log_Handler_Stream` | `StreamHandler` | Write to files or streams |
| Syslog | `Horde_Log_Handler_Syslog` | `SyslogHandler` | System logger integration |
| Systemd Journal | - | `SystemdJournalHandler` | **Modern only** - Native systemd journal with structured data |
| Scribe | `Horde_Log_Handler_Scribe` | `ScribeHandler` | Apache Scribe logging |
| Firebug | `Horde_Log_Handler_Firebug` | `FirebugHandler` | Browser console logging (deprecated) |
| CLI | `Horde_Log_Handler_Cli` | `CliHandler` | Command-line output |
| Null | `Horde_Log_Handler_Null` | `NullHandler` | Discard all logs |
| Mock | `Horde_Log_Handler_Mock` | `MockHandler` | Testing purposes |
| PSR-3 Adapter | - | `LoggerInterfaceHandler` | **Modern only** - Wrap any PSR-3 logger |

## Filters

| Filter | Legacy (lib/) | Modern (src/) | Notes |
|--------|---------------|---------------|-------|
| Minimum Level | `Horde_Log_Filter_Level` | `MinimumLevelFilter` | Log at or above priority (legacy name: Level) |
| Maximum Level | - | `MaximumLevelFilter` | **Modern only** - Log at or below priority |
| Exact Level | `Horde_Log_Filter_ExactLevel` | `ExactLevelFilter` | Log only specific priority |
| Message | `Horde_Log_Filter_Message` | `MessageFilter` | Regex-based message filtering |
| Constraint | `Horde_Log_Filter_Constraint` | `ConstraintFilter` | Custom constraint logic |
| Suppress | `Horde_Log_Filter_Suppress` | `SuppressFilter` | Suppress duplicate messages |

## Formatters

| Formatter | Legacy (lib/) | Modern (src/) | Notes |
|-----------|---------------|---------------|-------|
| Simple | `Horde_Log_Formatter_Simple` | `SimpleFormatter` | Basic text formatting |
| PSR-3 | - | `Psr3Formatter` | **Modern only** - PSR-3 standard format with context |
| XML | `Horde_Log_Formatter_Xml` | `XmlFormatter` | XML structured output |
| CLI | `Horde_Log_Formatter_Cli` | `CliFormatter` | Colored terminal output |

## Upgrading

See [doc/UPGRADING.md](doc/UPGRADING.md) for migration guides from legacy API to PSR-3.

## Requirements

- PHP 8.0 or later
- PSR-3 Logger Interface

## License

BSD-2-Clause

## Authors

- Mike Naberezny
- Chuck Hagenbuch
- Ralf Lang (current maintainer)
