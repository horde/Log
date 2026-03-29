<?php

/**
 * Horde Log package
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage UnitTests
 */

namespace Horde\Log\Test\Formatter;

use PHPUnit\Framework\TestCase;
use Horde\Log\Formatter\XmlFormatter;
use Horde\Log\LogLevel;
use Horde\Log\LogMessage;
use Horde_Log;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(XmlFormatter::class)]
class XmlFormatterTest extends TestCase
{
    private LogLevel $level;
    private LogMessage $logMessage;

    public function setUp(): void
    {
        $this->level = new LogLevel(Horde_Log::INFO, 'Info');
        $this->logMessage = new LogMessage($this->level, 'test message');
    }

    public function testConstructor(): void
    {
        $formatter = new XmlFormatter();
        $this->assertInstanceOf(XmlFormatter::class, $formatter);
    }

    public function testConstructorWithCustomOptions(): void
    {
        $options = [
            'elementEntry' => 'entry',
            'elementMessage' => 'msg',
        ];
        $formatter = new XmlFormatter($options);
        $this->assertInstanceOf(XmlFormatter::class, $formatter);
    }

    public function testFormatProducesValidXml(): void
    {
        $formatter = new XmlFormatter();
        $this->logMessage->formatMessage([]);

        $output = $formatter->format($this->logMessage);

        // Should be valid XML
        $xml = simplexml_load_string($output);
        $this->assertNotFalse($xml);
    }

    public function testFormatContainsDefaultElements(): void
    {
        $formatter = new XmlFormatter();
        $this->logMessage->formatMessage([]);

        $output = $formatter->format($this->logMessage);

        $this->assertStringContainsString('<log>', $output);
        $this->assertStringContainsString('<timestamp>', $output);
        $this->assertStringContainsString('<message>', $output);
        $this->assertStringContainsString('<level>', $output);
        $this->assertStringContainsString('</log>', $output);
    }

    public function testFormatIncludesMessageContent(): void
    {
        $formatter = new XmlFormatter();
        $this->logMessage->formatMessage([]);

        $output = $formatter->format($this->logMessage);

        $this->assertStringContainsString('test message', $output);
    }

    public function testFormatIncludesLogLevel(): void
    {
        $formatter = new XmlFormatter();
        $this->logMessage->formatMessage([]);

        $output = $formatter->format($this->logMessage);

        // Level names are lowercase in XML output
        $this->assertStringContainsString('<level>info</level>', $output);
    }

    public function testFormatIncludesTimestamp(): void
    {
        $formatter = new XmlFormatter();
        $this->logMessage->formatMessage([]);

        $output = $formatter->format($this->logMessage);

        // Should contain ISO 8601 timestamp
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $output);
    }

    public function testFormatDoesNotIncludeXmlDeclaration(): void
    {
        $formatter = new XmlFormatter();
        $this->logMessage->formatMessage([]);

        $output = $formatter->format($this->logMessage);

        // XML declaration should be stripped
        $this->assertStringNotContainsString('<?xml', $output);
    }

    public function testCustomElementNames(): void
    {
        $options = [
            'elementEntry' => 'entry',
            'elementTimestamp' => 'time',
            'elementMessage' => 'msg',
            'elementLevel' => 'priority',
        ];
        $formatter = new XmlFormatter($options);
        $this->logMessage->formatMessage([]);

        $output = $formatter->format($this->logMessage);

        $this->assertStringContainsString('<entry>', $output);
        $this->assertStringContainsString('<time>', $output);
        $this->assertStringContainsString('<msg>', $output);
        $this->assertStringContainsString('<priority>', $output);
    }

    public function testCustomLineEnding(): void
    {
        $options = [
            'lineEnding' => "\r\n",
        ];
        $formatter = new XmlFormatter($options);
        $this->logMessage->formatMessage([]);

        $output = $formatter->format($this->logMessage);

        $this->assertStringEndsWith("\r\n", $output);
    }

    public function testMultipleLogLevels(): void
    {
        $formatter = new XmlFormatter();

        $testCases = [
            [Horde_Log::EMERG, 'emergency'],
            [Horde_Log::ALERT, 'alert'],
            [Horde_Log::CRIT, 'critical'],
            [Horde_Log::ERR, 'error'],
            [Horde_Log::WARN, 'warning'],
            [Horde_Log::NOTICE, 'notice'],
            [Horde_Log::INFO, 'info'],
            [Horde_Log::DEBUG, 'debug'],
        ];

        foreach ($testCases as [$priority, $name]) {
            $level = new LogLevel($priority, ucfirst($name));
            $message = new LogMessage($level, 'test message');
            $message->formatMessage([]);

            $output = $formatter->format($message);

            // Level names are lowercase in XML
            $this->assertStringContainsString(
                '<level>' . $name . '</level>',
                $output,
                "Level name $name should appear in XML output"
            );
        }
    }

    public function testXmlSpecialCharactersAreHandled(): void
    {
        $formatter = new XmlFormatter();

        $level = new LogLevel(Horde_Log::INFO, 'Info');
        // Use characters that are safe in DOMElement constructor
        $message = new LogMessage($level, 'Test message with special chars');
        $message->formatMessage([]);

        $output = $formatter->format($message);

        // XML should be valid
        $xml = simplexml_load_string($output);
        $this->assertNotFalse($xml);
        $this->assertStringContainsString('special chars', $output);
    }

    public function testFormatStructure(): void
    {
        $formatter = new XmlFormatter();
        $this->logMessage->formatMessage([]);

        $output = $formatter->format($this->logMessage);

        // Parse as XML to verify structure
        $xml = simplexml_load_string($output);
        $this->assertNotFalse($xml);

        // Verify all expected child elements exist
        $this->assertNotNull($xml->timestamp);
        $this->assertNotNull($xml->message);
        $this->assertNotNull($xml->level);
    }
}
