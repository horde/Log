<?php
/**
 * Tests for the Logger Interface Handler
 *
 * @author     Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
declare(strict_types=1);

namespace Horde\Log\Handler;

use PHPUnit\Framework\TestCase;
use Horde\Log\LogFilter;
use Horde\Log\LogHandler;
use Horde\Log\LogFormatter;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use Horde\Log\LogException;
use Horde\Log\Logger;
use Horde\Log\LogLevels;
use InvalidArgumentException;
use Horde_Log;

class LoggerInterfaceHandlerTest extends TestCase
{
    public function setUp(): void
    {
        date_default_timezone_set('America/New_York');
        $this->level1 = new LogLevel(Horde_Log::ALERT, 'Alert');
        $this->message1 = 'this is an emergency!';
        $this->logMessage1 = new LogMessage($this->level1, $this->message1, ['timestamp' => date('c')]);

        $this->logging = new Logger();
        $this->loggerinterfacehandler = new LoggerInterfaceHandler($this->logging);
    }

    public function testLog()
    {
        $this->assertNull($this->loggerinterfacehandler->log($this->logMessage1));
    }

    public function testWrite()
    {
        $this->logMessage1->formatMessage([]);
        $this->assertTrue($this->loggerinterfacehandler->write($this->logMessage1));
    }
}
