# Upgrading Horde Log

## Upgrading from 3.0.0-beta6

### Timestamp is now a first-class DateTimeImmutable property

`LogMessage` no longer stores the timestamp as a unix integer in the context array.
Instead it holds a typed `DateTimeImmutable` property accessible via `$event->timestamp()`.

**What changed:**

- If you pass `context['timestamp']` as a `DateTimeInterface`, it is promoted to the
  record timestamp and removed from context.
- Any other type (int, string) stays in context as user data but does not affect
  the record timestamp. A fresh `DateTimeImmutable` is generated automatically.
- `SimpleFormatter` always renders `%timestamp%` as ISO 8601 from the record property.
- `XmlFormatter` uses the record timestamp instead of generating a new one at format time.

**Before:**
```php
// Timestamp was a unix int in context
$msg = new LogMessage($level, 'hello');
$msg->context()['timestamp']; // 1716825600 (int)
```

**After:**
```php
use DateTimeImmutable;

// Record timestamp is a typed property
$msg = new LogMessage($level, 'hello');
$msg->timestamp(); // DateTimeImmutable

// Pass a DateTimeInterface to control the record time
$msg = new LogMessage($level, 'hello', [
    'timestamp' => new DateTimeImmutable('2025-06-15T12:00:00Z'),
]);
$msg->timestamp()->format('c'); // "2025-06-15T12:00:00+00:00"

// Non-DateTimeInterface values stay in context as user data
$msg = new LogMessage($level, 'hello', ['timestamp' => 1716825600]);
$msg->context()['timestamp']; // 1716825600 (unchanged)
$msg->timestamp();            // auto-generated DateTimeImmutable (current time)
```

**If you relied on `context['timestamp']` being an integer:**

Replace `$event->context()['timestamp']` with `$event->timestamp()->getTimestamp()`
for a unix integer, or `$event->timestamp()->format('c')` for ISO 8601.

---

## Upgrading from Horde_Log_Logger

The PSR-0 legacy API (`Horde_Log_*` in `lib/`) remains fully functional for now but is in bugfix-only mode.
Migrate to PSR-4 (`Horde\Log\*` in `src/`) for modern dependency injection and structured logging.
If migrating at interface level is not an option right now, at least consider migrating to the wrapper around the PSR-3 logger.
The native implementation of the old API will eventually be dropped.

### Basic Migration

**Before (PSR-0):**
```php
$logger = new Horde_Log_Logger(
    new Horde_Log_Handler_Stream('/var/log/app.log')
);

$logger->info('User logged in: ' . $username);
$logger->err('Authentication failed for ' . $username);
```

**After (PSR-4):**
```php
use Horde\Log\Logger;
use Horde\Log\Handler\StreamHandler;

$logger = new Logger();
$logger->addHandler(new StreamHandler('/var/log/app.log'));

$logger->info('User logged in', ['username' => $username]);
$logger->error('Authentication failed', ['username' => $username]);
```

### Log Level Changes

PSR-3 uses different method names:

| PSR-0 Method | PSR-4 Method | Level |
|--------------|--------------|-------|
| `emerg()`    | `emergency()` | 0 |
| `alert()`    | `alert()`     | 1 |
| `crit()`     | `critical()`  | 2 |
| `err()`      | `error()`     | 3 |
| `warn()`     | `warning()`   | 4 |
| `notice()`   | `notice()`    | 5 |
| `info()`     | `info()`      | 6 |
| `debug()`    | `debug()`     | 7 |

### Handler Migration

Handler classes moved from `Horde_Log_Handler_*` to `Horde\Log\Handler\*Handler`:

```php
// Before
new Horde_Log_Handler_Stream($path)
new Horde_Log_Handler_Syslog()
new Horde_Log_Handler_Null()

// After
use Horde\Log\Handler\StreamHandler;
use Horde\Log\Handler\SyslogHandler;
use Horde\Log\Handler\NullHandler;

new StreamHandler($path)
new SyslogHandler()
new NullHandler()
```

### Filter Migration

```php
// Before
$logger->addFilter(new Horde_Log_Filter_Level(Horde_Log::INFO));

// After
use Horde\Log\Filter\MinimumLevelFilter;
use Horde\Log\LogLevel;

$logger->addFilter(new MinimumLevelFilter(LogLevel::INFO));
```

### Formatter Migration

```php
// Before
$handler = new Horde_Log_Handler_Stream($path);
$handler->setFormatter(new Horde_Log_Formatter_Simple());

// After
use Horde\Log\Handler\StreamHandler;
use Horde\Log\Formatter\SimpleFormatter;

$handler = new StreamHandler($path);
$handler->addFormatter(new SimpleFormatter());
```

### Structured Context Arrays with Placeholders

Replace string concatenation with context arrays and placeholder syntax:

**Before:**
```php
$logger->info("User $username logged in from $ip");
$logger->err("Authentication failed: " . $e->getMessage());
$logger->debug("Token expires at " . date('Y-m-d H:i:s', $expiresAt));
```

**After:**
```php
$logger->info('User {username} logged in from {ip}', [
    'username' => $username,
    'ip' => $ip,
]);

$logger->error('Authentication failed', [
    'exception' => $e,  // Full exception object
    'username' => $username,
]);

$logger->debug('Token {jti} expires at {expires_at}', [
    'jti' => $jti,
    'expires_at' => $expiresAt,  // Unix timestamp
]);
```

**Placeholder syntax:**
- Use `{key}` in message where `key` matches context array key
- Message should be static and translation-ready
- Placeholders are interpolated by PSR-3 handler
- Context data is preserved for structured backends

**Benefits:**
- Human-readable messages with interpolated values
- Type-safe context data (booleans, integers, timestamps)
- Backends can index and query context attributes separately
- Query by attribute: `journalctl username=alice`
- Translation-ready static message templates

**Exception handling:**

Always pass the full exception object in the `exception` key:

```php
try {
    // ...
} catch (Exception $e) {
    $logger->error('Authentication failed for {username}', [
        'exception' => $e,  // PSR-3 compliant - full object
        'username' => $username,
    ]);
}
```

PSR-3 handlers will extract message and stack trace from the exception object.

**Note:** Some logging use cases with purely machine-consumed logs (systemd journal queries, log aggregation pipelines) may omit placeholders and use short message strings when context attributes are the primary interface. This is acceptable but the placeholder pattern is the PSR-3 standard.

---

## Upgrading from Horde::log()

The static `Horde::log()` method should be replaced with PSR-3 `LoggerInterface` for testability and flexibility.

### Quick Reference

**Before:**
```php
Horde::log($message, 'DEBUG');
Horde::log($message, 'INFO');
Horde::log($message, 'NOTICE');
Horde::log($message, 'WARN');
Horde::log($message, 'ERR');
Horde::log($message, 'CRIT');
Horde::log($message, 'ALERT');
Horde::log($message, 'EMERG');
```

**After:**
```php
$this->logger->debug($message, $context);
$this->logger->info($message, $context);
$this->logger->notice($message, $context);
$this->logger->warning($message, $context);
$this->logger->error($message, $context);
$this->logger->critical($message, $context);
$this->logger->alert($message, $context);
$this->logger->emergency($message, $context);
```

### Pattern 1: DI-Ready Classes

**For classes with constructor injection:**

```php
// Before
class AuthenticationService
{
    public function __construct(
        private readonly Horde_Registry $registry,
    ) {}

    public function authenticate(string $username): bool
    {
        Horde::log("Attempting auth for $username", 'DEBUG');
        // ...
    }
}

// After
use Psr\Log\LoggerInterface;

class AuthenticationService
{
    public function __construct(
        private readonly Horde_Registry $registry,
        private readonly LoggerInterface $logger,
    ) {}

    public function authenticate(string $username): bool
    {
        $this->logger->debug('Authentication attempt', [
            'username' => $username,
        ]);
        // ...
    }
}
```

**Update factory:**
```php
public function create(Injector $injector): AuthenticationService
{
    $registry = $injector->get('Horde_Registry');
    $logger = $injector->get(LoggerInterface::class);
    return new AuthenticationService($registry, $logger);
}
```

**Note:** The PSR-3 logger binding is provided by the Horde core framework, no additional setup required in applications.

**Update tests:**
```php
use Psr\Log\LoggerInterface;

public function testAuthentication(): void
{
    $registry = $this->createMock(Horde_Registry::class);
    $logger = $this->createMock(LoggerInterface::class);

    $service = new AuthenticationService($registry, $logger);
    // ...
}
```

### Pattern 2: Entry Scripts

**For scripts with $injector available:**

```php
// Before
Horde::log("Processing request from $username", 'INFO');

// After
$logger = $injector->get(LoggerInterface::class);
$logger->info('Processing request', [
    'username' => $username,
    'request_path' => $_SERVER['REQUEST_URI'] ?? 'unknown',
]);
```

Note: Check if $injector is already made accessible via global $injector statement. Otherwise use $GLOBALS['injector']->get()

### Pattern 3: Legacy Classes Without DI

Add DI incrementally:**

```php
class LegacyController
{
    private LoggerInterface $logger;

    public function __construct()
    {
        global $injector;
        $this->logger = $injector->get(LoggerInterface::class);
    }

    public function handle()
    {
        $this->logger->info('Handling request');
    }
}
```

### String Concatenation to Context Arrays

**Before:**
```php
Horde::log("User $username logged in from IP $ip", 'INFO');
Horde::log("Auth failed for user=$username, reason=$reason", 'ERR');
Horde::log("Token JTI=$jti expires at $timestamp", 'DEBUG');
```

**After:**
```php
$this->logger->info('User logged in', [
    'username' => $username,
    'ip' => $ip,
]);

$this->logger->error('Authentication failed', [
    'username' => $username,
    'reason' => $reason,
]);

$this->logger->debug('Token issued', [
    'jti' => $jti,
    'expires_at' => $timestamp,
]);
```

### Context Attribute Guidelines

**Use snake_case for consistency:**
```php
['username' => $user, 'session_id' => $sid, 'has_jwt' => true]
```

**Use native types:**
```php
// Good
['success' => true, 'count' => 42, 'expires_at' => 1711756800]

// Bad
['success' => 'true', 'count' => '42', 'expires_at' => '2026-03-29']
```

**Standard attribute names:**
- `username`, `user_id` (not `user`, `login`, `uid`)
- `session_id` (not `sid`, `session`)
- `jti` (not `jwt_id`, `token_id`)
- `success` (boolean result)
- `reason` (failure reason code)
- `exception`, `exception_class` (error details)
- `request_method`, `request_path`, `request_ip`
- `has_*` (boolean flags: `has_jwt_service`, `has_cookie`)

---

## Upgrading in Horde Core Framework

Special considerations when upgrading framework code that other applications depend on.

### PSR-3 Logger Availability

The PSR-3 `LoggerInterface` is available through the injector in Horde applications. The binding is provided by `Horde\Horde\Factory\LoggerFactory` which wraps the existing `Horde_Log_Logger` with a PSR-3 adapter.

**Getting the logger:**

```php
// In any code with access to $injector
$logger = $injector->get(LoggerInterface::class);
```

The factory automatically wraps the configured `Horde_Log_Logger` instance, maintaining compatibility with existing Horde logging configuration (log files, handlers, formatters).

### Constructor Injection (Preferred)

**For new classes or when BC breaks are acceptable:**

```php
use Psr\Log\LoggerInterface;

class MyService
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function doWork(): void
    {
        $this->logger->info('Work started');
    }
}
```

**Parameter order matters** - required before optional:

```php
// Good
public function __construct(
    private readonly Horde_Registry $registry,
    private readonly LoggerInterface $logger,        // Required
    private readonly ?JwtService $jwt = null,        // Optional last
) {}

// Bad - syntax error
public function __construct(
    private readonly Horde_Registry $registry,
    private readonly ?JwtService $jwt = null,        // Optional
    private readonly LoggerInterface $logger,        // Required after optional
) {}
```

### Fallback to Default Logger

**When logger injection fails gracefully:**

```php
class MyService
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly Horde_Registry $registry,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }
}
```

### Fallback to Null Logger

**For optional logging without hard dependency:**

```php
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

class OptionalLoggingService
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}
}
```

### LoggerAwareInterface (Last Resort)

**Only use when constructor injection would break BC:**

```php
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ExistingPublicService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        // Existing signature preserved for BC
        $this->logger = new NullLogger();
    }

    public function doWork(): void
    {
        $this->logger->info('Working');
    }
}
```

**Setup:**
```php
$service = new ExistingPublicService();
$service->setLogger($injector->get(LoggerInterface::class));
```

**Drawbacks:**
- Two-step initialization
- Easy to forget setLogger() call
- Not enforced by type system

**Prefer constructor injection** for new code and internal framework classes.

### Adding Context Attributes

**Always add context when upgrading:**

```php
// Before
Horde::log("User $username failed auth", 'ERR');

// After - basic
$this->logger->error('Authentication failed', [
    'username' => $username,
]);

// After - rich context (better)
$this->logger->error('Authentication failed', [
    'username' => $username,
    'reason' => 'invalid_password',
    'request_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
]);
```

**Authentication events:**
```php
$this->logger->info('User authenticated', [
    'username' => $username,
    'session_id' => $sessionId,
    'jti' => $jti,
    'has_jwt' => true,
]);
```

**JWT operations:**
```php
$this->logger->debug('JWT tokens issued', [
    'username' => $username,
    'jti' => $refreshJti,
    'access_token_expires_at' => $accessExpiresAt,
]);
```

**Session management:**
```php
$this->logger->debug('Session migrated to JTI', [
    'old_session_id' => $oldId,
    'new_session_id' => $jti,
    'username' => $username,
]);
```

**Error handling:**
```php
$this->logger->error('Service failure', [
    'exception' => $e->getMessage(),
    'exception_class' => get_class($e),
    'username' => $username ?? 'anonymous',
]);
```

**Benefits of rich context:**
- Queryable by log backends (journalctl, Logstash, Elasticsearch)
- Correlation across services by username, JTI, session_id
- Security audit trails
- Performance analysis
- Faster debugging

### Verifying Logger Availability

**Classes instantiated after bootstrap** can safely assume logger is available:
- Route handlers (middleware runs after bootstrap)
- Service factories (called after registry init)
- API controllers (routed after config load)

**Classes instantiated during early bootstrap** may need null logger fallback if instantiated before the injector is fully configured.

**Example with fallback:**
```php
try {
    $logger = $injector->get(LoggerInterface::class);
} catch (Exception $e) {
    $logger = new NullLogger();
}
```

---

## Testing

**Mock the logger in unit tests:**

```php
use Psr\Log\LoggerInterface;

public function testService(): void
{
    $logger = $this->createMock(LoggerInterface::class);

    // Optional: verify log calls
    $logger->expects($this->once())
        ->method('error')
        ->with(
            'Authentication failed',
            $this->callback(function ($context) {
                return $context['username'] === 'testuser'
                    && $context['reason'] === 'invalid_password';
            })
        );

    $service = new MyService($logger);
    $service->authenticate('testuser', 'wrong');
}
```

**Test with NullLogger for simpler tests:**

```php
use Psr\Log\NullLogger;

public function testServiceLogic(): void
{
    // Don't care about log calls - just test logic
    $service = new MyService(new NullLogger());
    $result = $service->doWork();

    $this->assertTrue($result);
}
```

---

## Configuration

The PSR-3 `LoggerInterface` is automatically available in Horde applications through the injector. The framework provides `Horde\Horde\Factory\LoggerFactory` which wraps the existing `Horde_Log_Logger` with a PSR-3 adapter.

**No application-level configuration required.** The logger respects existing Horde logging configuration (log files, handlers, formatters) defined in `conf.php`.

**Usage in application code:**

```php
// In factories
$logger = $injector->get(LoggerInterface::class);

// In classes with constructor injection
public function __construct(
    private readonly LoggerInterface $logger,
) {}
```

Both `Horde_Log_Logger` (legacy) and `Psr\Log\LoggerInterface` (modern) work simultaneously, allowing gradual migration.

## References

- PSR-3 Specification: https://www.php-fig.org/psr/psr-3/
- PSR-3 Interfaces: https://github.com/php-fig/log
- RFC 5424 Log Levels: https://tools.ietf.org/html/rfc5424
- Larry Garfield on using PSR-3 Placeholders properly: https://www.garfieldtech.com/blog/psr-3-properly