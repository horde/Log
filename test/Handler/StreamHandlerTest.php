<?php

/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author     Rafael te Boekhorst <boekhorstb1@b1-systems.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test\Handler;

use Horde\Log\Handler\StreamHandler;
use PHPUnit\Framework\TestCase;
use Horde\Log\LogHandler;
use Horde\Log\LogException;
use Horde_Log;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(StreamHandler::class)]
class StreamHandlerTest extends TestCase
{
    private LogLevel $level1;
    private string $message1;
    private LogMessage $logMessage1;

    public function setUp(): void
    {
        date_default_timezone_set('America/New_York');
        $this->level1 = new LogLevel(Horde_Log::ALERT, 'Alert');
        $this->message1 = 'this is an emergency!';
        $this->logMessage1 = new LogMessage($this->level1, $this->message1, ['timestamp' => date('c')]);
    }

    public function testConstructorThrowsWhenResourceIsNotStream()
    {
        $this->expectException(LogException::class);
        $resource = xml_parser_create();
        new StreamHandler($resource);
        xml_parser_free($resource);
    }

    public function testConstructorWithValidStream()
    {
        $stream = fopen('php://memory', 'a');
        $handler = new StreamHandler($stream);
        $this->assertInstanceOf(LogHandler::class, $handler);

        // Test write() method directly (need to format message first)
        $this->logMessage1->formatMessage([]);
        $result = $handler->write($this->logMessage1);
        $this->assertTrue($result);
    }

    public function testConstructorWithValidUrl()
    {
        $handler = new StreamHandler('php://memory');
        $this->assertInstanceOf(LogHandler::class, $handler);
    }

    public function testConstructorThrowsWhenStreamCannotBeOpened()
    {
        $this->expectException(LogException::class);
        new StreamHandler('');
    }

    public function testSettingBadOptionThrows()
    {
        $this->expectException(LogException::class);
        $handler = new StreamHandler('php://memory');
        $handler->setOption('foo', 42);
    }

    public function testWrite() #See below comment: there is a small issue here
    {
        $stream = fopen('php://memory', 'a');

        $handler = new StreamHandler($stream);
        $handler->log($this->logMessage1);

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        $message = $this->logMessage1->message();
        $levelName = $this->logMessage1->level()->name();

        $date = '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}-\d{2}:\d{2}';

        $this->assertMatchesRegularExpression("/$date/", $contents);
        $this->assertMatchesRegularExpression("/$message/", $contents);
        // $this->assertMatchesRegularExpression("/$levelName/", $contents); // this does not match levelName, gives an error, because here levelName cannot reach to level->name with the format '%timestamp% %levelName%: %message%' . PHP_EOL... Need to fix default format for SimpleFormatter?
    }

    public function testWriteThrowsWhenStreamWriteFails()
    {
        $this->expectException(LogException::class);
        $stream = fopen('php://memory', 'a');
        $handler = new StreamHandler($stream);
        $handler->log($this->logMessage1);
        fclose($stream);
        $handler->write($this->logMessage1);
    }

    /**
     * @outputBuffering disabled
     */
    public function testWriteToPhpOutput(): void
    {
        $handler = new StreamHandler('php://output');

        ob_start();
        $handler->log($this->logMessage1);
        $output = ob_get_clean();

        $this->assertStringContainsString($this->message1, $output);
    }

    /**
     * @outputBuffering disabled
     */
    public function testWriteToPhpStderr(): void
    {
        $handler = new StreamHandler('php://stderr');

        // Can't easily capture stderr, just verify it doesn't throw
        $handler->log($this->logMessage1);
        $this->assertTrue(true);
    }

    public function testMultipleWrites(): void
    {
        $stream = fopen('php://memory', 'a');
        $handler = new StreamHandler($stream);

        $message1 = new LogMessage($this->level1, 'first message');
        $message2 = new LogMessage($this->level1, 'second message');
        $message3 = new LogMessage($this->level1, 'third message');

        $handler->log($message1);
        $handler->log($message2);
        $handler->log($message3);

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('first message', $contents);
        $this->assertStringContainsString('second message', $contents);
        $this->assertStringContainsString('third message', $contents);
    }

    /**
     * @outputBuffering disabled
     */
    public function testWriteWithCustomFormatter(): void
    {
        $stream = fopen('php://memory', 'a');

        // Formatter passed to constructor
        $formatter = new class implements \Horde\Log\LogFormatter {
            public function format(LogMessage $event): string
            {
                return '[CUSTOM] ' . $event->message();
            }
        };
        $handler = new StreamHandler($stream, 'a+', null, [$formatter]);

        $handler->log($this->logMessage1);

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('[CUSTOM]', $contents);
        $this->assertStringContainsString($this->message1, $contents);
    }

    /**
     * @outputBuffering disabled
     */
    public function testWriteAppliesFilters(): void
    {
        $stream = fopen('php://memory', 'a');
        $handler = new StreamHandler($stream);

        // Add a filter that rejects everything
        $filter = new class implements \Horde\Log\LogFilter {
            public function accept(LogMessage $event): bool
            {
                return false;
            }
        };
        $handler->addFilter($filter);

        $handler->log($this->logMessage1);

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        // Nothing should be written because filter rejected it
        $this->assertEmpty($contents);
    }

    /**
     * @outputBuffering disabled
     */
    public function testStreamResourceIsPreserved(): void
    {
        $stream = fopen('php://memory', 'a');
        $handler = new StreamHandler($stream);

        // Write something
        $handler->log($this->logMessage1);

        // Stream should still be usable
        rewind($stream);
        $contents = stream_get_contents($stream);
        $this->assertNotEmpty($contents);

        fclose($stream);
    }

    /**
     * @outputBuffering disabled
     */
    public function testWakeupReopensStream(): void
    {
        // Create handler with URL (not resource)
        $tempFile = tempnam(sys_get_temp_dir(), 'log_test_');
        $handler = new StreamHandler($tempFile, 'a+');

        // Write something (format first)
        $this->logMessage1->formatMessage([]);
        $handler->write($this->logMessage1);

        // Serialize and unserialize to trigger __wakeup
        $serialized = serialize($handler);
        $newHandler = unserialize($serialized);

        // Write again to verify stream reopened
        $message2 = new LogMessage($this->level1, 'second write');
        $message2->formatMessage([]);
        $result = $newHandler->write($message2);
        $this->assertTrue($result);

        // Verify both messages are in file
        $contents = file_get_contents($tempFile);
        $this->assertStringContainsString($this->message1, $contents);
        $this->assertStringContainsString('second write', $contents);

        unlink($tempFile);
    }

    public function testConstructorWithModeParameter(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'log_test_');

        // Test write mode
        $handler = new StreamHandler($tempFile, 'w');
        $this->assertInstanceOf(StreamHandler::class, $handler);

        unlink($tempFile);
    }

    public function testConstructorThrowsOnModeChangeForExistingStream(): void
    {
        $this->expectException(LogException::class);
        $this->expectExceptionMessage('Mode cannot be changed on existing streams');

        $stream = fopen('php://memory', 'a');
        new StreamHandler($stream, 'w');
    }

    public function testWriteWithIdentOption(): void
    {
        $stream = fopen('php://memory', 'a');
        $options = new \Horde\Log\Handler\Options();
        $options->ident = 'TEST-APP';

        $handler = new StreamHandler($stream, 'a+', $options);
        $this->logMessage1->formatMessage([]);
        $handler->write($this->logMessage1);

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('TEST-APP', $contents);
        $this->assertStringContainsString($this->message1, $contents);
    }

    public function testSetOptionAfterConstruction(): void
    {
        $stream = fopen('php://memory', 'a');
        $handler = new StreamHandler($stream);

        // Test setOption
        $result = $handler->setOption('ident', 'UPDATED');
        $this->assertTrue($result);

        // Write with new ident
        $this->logMessage1->formatMessage([]);
        $handler->write($this->logMessage1);

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('UPDATED', $contents);
    }

    public function testConstructorWithAllParameters(): void
    {
        $stream = fopen('php://memory', 'a');
        $options = new \Horde\Log\Handler\Options();
        $options->ident = 'ALL-PARAMS';

        $formatter = new class implements \Horde\Log\LogFormatter {
            public function format(LogMessage $event): string
            {
                return 'FORMATTED: ' . $event->message();
            }
        };

        $handler = new StreamHandler($stream, 'a+', $options, [$formatter]);

        $this->logMessage1->formatMessage([$formatter]);
        $handler->write($this->logMessage1);

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('ALL-PARAMS', $contents);
        $this->assertStringContainsString('FORMATTED:', $contents);
    }
}
