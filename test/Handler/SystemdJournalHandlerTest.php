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
 * @subpackage UnitTests
 */

namespace Horde\Log\Test\Handler;

use Horde\Log\Handler\SystemdJournalHandler;
use Horde\Log\Handler\SystemdJournalOptions;
use Horde\Log\LogException;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use Horde_Log;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SystemdJournalHandler::class)]
class SystemdJournalHandlerTest extends TestCase
{
    private LogLevel $infoLevel;
    private LogLevel $errorLevel;
    private LogMessage $simpleMessage;
    private LogMessage $contextMessage;
    private SystemdJournalHandler $handler;

    public function setUp(): void
    {
        date_default_timezone_set('UTC');

        $this->infoLevel = new LogLevel(Horde_Log::INFO, 'Info');
        $this->errorLevel = new LogLevel(Horde_Log::ERR, 'Error');

        $this->simpleMessage = new LogMessage(
            $this->infoLevel,
            'Simple test message'
        );
        $this->simpleMessage->formatMessage([]);

        $this->contextMessage = new LogMessage(
            $this->errorLevel,
            'Message with context',
            [
                'file' => '/path/to/file.php',
                'line' => 42,
                'function' => 'testFunction',
                'custom_field' => 'custom_value',
                'timestamp' => time(),
            ]
        );
        $this->contextMessage->formatMessage([]);

        $this->handler = new SystemdJournalHandler();
    }

    /**
     * Test that isAvailable checks for journal socket
     */
    public function testIsAvailable(): void
    {
        // Check if we're on a systemd system
        $hasJournal = file_exists('/run/systemd/journal/socket');

        $this->assertSame($hasJournal, $this->handler->isAvailable());
    }

    /**
     * Test basic write functionality when journal is available
     */
    public function testWriteSimpleMessage(): void
    {
        if (!$this->handler->isAvailable()) {
            $this->markTestSkipped('Systemd journal not available on this system');
        }

        $result = $this->handler->write($this->simpleMessage);
        $this->assertTrue($result);
    }

    /**
     * Test writing message with context fields
     */
    public function testWriteMessageWithContext(): void
    {
        if (!$this->handler->isAvailable()) {
            $this->markTestSkipped('Systemd journal not available on this system');
        }

        $result = $this->handler->write($this->contextMessage);
        $this->assertTrue($result);
    }

    /**
     * Test exception when journal is not available
     */
    public function testExceptionWhenJournalNotAvailable(): void
    {
        $options = new SystemdJournalOptions();
        $options->socketPath = '/tmp/nonexistent/socket';

        $handler = new SystemdJournalHandler($options);

        $this->expectException(LogException::class);
        $this->expectExceptionMessage('Systemd journal socket not available');

        $handler->write($this->simpleMessage);
    }

    /**
     * Test custom identifier
     */
    public function testCustomIdentifier(): void
    {
        if (!$this->handler->isAvailable()) {
            $this->markTestSkipped('Systemd journal not available on this system');
        }

        $options = new SystemdJournalOptions();
        $options->ident = 'horde-test-' . uniqid();

        $handler = new SystemdJournalHandler($options);
        $result = $handler->write($this->simpleMessage);

        $this->assertTrue($result);
    }

    /**
     * Test additional fields configuration
     */
    public function testAdditionalFields(): void
    {
        if (!$this->handler->isAvailable()) {
            $this->markTestSkipped('Systemd journal not available on this system');
        }

        $options = new SystemdJournalOptions();
        $options->ident = 'horde-test-' . uniqid();
        $options->additionalFields = [
            'HORDE_VERSION' => '6.0',
            'HORDE_COMPONENT' => 'log',
            'ENVIRONMENT' => 'test',
        ];

        $handler = new SystemdJournalHandler($options);
        $result = $handler->write($this->simpleMessage);

        $this->assertTrue($result);

        // Note: To verify the fields were actually written, you would need to
        // query journalctl, which is beyond the scope of unit tests but can be
        // verified in integration tests
    }

    /**
     * Test priority mapping
     */
    public function testPriorityMapping(): void
    {
        if (!$this->handler->isAvailable()) {
            $this->markTestSkipped('Systemd journal not available on this system');
        }

        $levels = [
            new LogLevel(Horde_Log::EMERG, 'Emergency'),
            new LogLevel(Horde_Log::ALERT, 'Alert'),
            new LogLevel(Horde_Log::CRIT, 'Critical'),
            new LogLevel(Horde_Log::ERR, 'Error'),
            new LogLevel(Horde_Log::WARN, 'Warning'),
            new LogLevel(Horde_Log::NOTICE, 'Notice'),
            new LogLevel(Horde_Log::INFO, 'Info'),
            new LogLevel(Horde_Log::DEBUG, 'Debug'),
        ];

        foreach ($levels as $level) {
            $message = new LogMessage($level, 'Test message at ' . $level->name());
            $message->formatMessage([]);

            $result = $this->handler->write($message);
            $this->assertTrue($result);
        }
    }

    /**
     * Test field name sanitization
     */
    public function testFieldNameSanitization(): void
    {
        if (!$this->handler->isAvailable()) {
            $this->markTestSkipped('Systemd journal not available on this system');
        }

        // Create message with context keys that need sanitization
        $message = new LogMessage(
            $this->infoLevel,
            'Test sanitization',
            [
                'my-field' => 'value1',          // hyphen -> underscore
                'field.with.dots' => 'value2',   // dots -> underscores
                'UPPERCASE' => 'value3',         // already uppercase
                '_reserved' => 'value4',         // leading underscore (should be filtered)
            ]
        );
        $message->formatMessage([]);

        $result = $this->handler->write($message);
        $this->assertTrue($result);
    }

    /**
     * Test socket reuse
     */
    public function testSocketReuse(): void
    {
        if (!$this->handler->isAvailable()) {
            $this->markTestSkipped('Systemd journal not available on this system');
        }

        // Write multiple messages - socket should be reused
        for ($i = 0; $i < 5; $i++) {
            $message = new LogMessage(
                $this->infoLevel,
                'Message ' . $i
            );
            $message->formatMessage([]);

            $result = $this->handler->write($message);
            $this->assertTrue($result);
        }
    }

    /**
     * Test context key to field name mapping
     */
    public function testContextKeyMapping(): void
    {
        if (!$this->handler->isAvailable()) {
            $this->markTestSkipped('Systemd journal not available on this system');
        }

        // Test that special context keys map to standard journal fields
        $message = new LogMessage(
            $this->infoLevel,
            'Test mapping',
            [
                'file' => 'test.php',         // -> CODE_FILE
                'line' => 123,                 // -> CODE_LINE
                'function' => 'testFunc',      // -> CODE_FUNC
                'pid' => 12345,                // -> SYSLOG_PID
            ]
        );
        $message->formatMessage([]);

        $result = $this->handler->write($message);
        $this->assertTrue($result);
    }

    /**
     * Test that non-scalar context values are ignored
     */
    public function testNonScalarContextIgnored(): void
    {
        if (!$this->handler->isAvailable()) {
            $this->markTestSkipped('Systemd journal not available on this system');
        }

        $message = new LogMessage(
            $this->infoLevel,
            'Test non-scalar',
            [
                'string' => 'value',
                'int' => 42,
                'float' => 3.14,
                'bool' => true,
                'array' => ['should', 'be', 'ignored'],
                'object' => (object)['should' => 'be ignored'],
                'null' => null,
            ]
        );
        $message->formatMessage([]);

        // Should not throw exception, non-scalar values are silently ignored
        $result = $this->handler->write($message);
        $this->assertTrue($result);
    }

    public function testSetOption(): void
    {
        $handler = new SystemdJournalHandler();

        // Test setOption with valid option
        $result = $handler->setOption('ident', 'TEST-APP');
        $this->assertTrue($result);
    }

    public function testSetOptionWithInvalidOption(): void
    {
        $this->expectException(LogException::class);

        $handler = new SystemdJournalHandler();
        $handler->setOption('invalidOption', 'value');
    }

    public function testConstructorWithFormattersAndFilters(): void
    {
        $options = new SystemdJournalOptions();
        $handler = new SystemdJournalHandler($options, [], []);

        $this->assertInstanceOf(SystemdJournalHandler::class, $handler);
    }
}
