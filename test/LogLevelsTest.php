<?php
/**
 * Tests for the LogLevels
 *
 * @author     Rafael te Boekhorst <boekhorst@b1-systems.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
declare(strict_types=1);

namespace Horde\Log\Test;

use PHPUnit\Framework\TestCase;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Horde\Log\LogLevels;
use Horde_Log;

class LogLevelsTest extends TestCase
{
    public function setUp(): void
    {
        date_default_timezone_set('America/New_York');
        $this->level1 = new LogLevel(Horde_Log::ALERT, 'Alert');
        $this->message1 = 'this is an emergency! Really! ...';
        $this->logMessage1 = new LogMessage($this->level1, $this->message1, ['timestamp' => date('c')]);
        $this->loadlevels[] = $this->level1;
        $this->loglevels = new LogLevels($this->loadlevels);
    }

    public function testLogLevelsConstructorWithCustomLogLevel()
    {
        // Adding new LogLevelObject to the array $loadlevels[]
        $level24 = new LogLevel(24, 'Weirdness warning');
        $loadlevels[] = $level24;

        // Adding default LoglevelObject from setUp() to the array loadlevels[]
        $loadlevels[] = $this->level1;

        // Creating a LogLevels Object with the array loadlevels[]
        $LogLevelsObject = new LogLevels($loadlevels);

        // Testing if the Object $LogLevelsObject is created and is of Class LogLevels
        $this->assertInstanceOf(LogLevels::class, $LogLevelsObject);
    }

    public function testLogLevelsRegisterFunctionWithGetByLevelName()
    {
        // Registering new LogLevelObject in default loglevels object
        $level35 = new LogLevel(35, 'Strangeness warning');
        $this->loglevels->register($level35);

        // Asserting that returned LevelName is same as newly created Loglevel
        $this->assertEquals($this->loglevels->getByLevelName('strangeness warning'), $level35);
    }

    public function testLogLevelsRegisterFunctionWithGetByCriticality()
    {
        // Registering new LogLevelObject in default loglevels object
        $level36 = new LogLevel(36, 'Absurdness warning');
        $this->loglevels->register($level36);
        $this->assertEquals($this->loglevels->getByCriticality(36), $level36);
    }

    public function testBothInitMethods()
    {
        $initWithAliasLevels = $this->loglevels->initWithAliasLevels();
        $initWithCanonicalLevels = $this->loglevels->initWithCanonicalLevels();


        $levelNamesCannonical = [
            0 => 'emergency',
            1 => 'alert',
            2 => 'critical',
            3 => 'error',
            4 => 'warning',
            5 => 'notice',
            6 => 'info',
            7 => 'debug'
        ];

        $levelNamesAliases = [
            0 => 'emerg',
            2 => 'crit',
            3 => 'err',
            4 =>  'warn',
            5 => 'information',
            6 => 'informational'
        ];

        // testing cannonical names and levels
        foreach ($levelNamesCannonical as $level => $names) {
            $bynameCanonical = $initWithCanonicalLevels->getByLevelName($names);
            $bycriticalityCannonical = $initWithCanonicalLevels->getByCriticality($level);
            $this->assertEquals($bynameCanonical, $bycriticalityCannonical);
        }

        // testing alias names and levels
        $count = 0;

        foreach ($levelNamesAliases as $level => $alias) {
            $bynameAlias = $initWithAliasLevels->getByLevelName($alias);

            if ($alias == "information") {
                $bycriticalityAlias =  $initWithAliasLevels->getByCriticality(6);
            } else {
                $bycriticalityAlias =  $initWithAliasLevels->getByCriticality($level);
            }

            if ($bynameAlias->criticality() == 6) { // this if-else-statements are necessary because the aliases for info are longer than te cannonical names in case of info (level 6)
                $this->assertStringContainsString($bycriticalityAlias->name(), $bynameAlias->name());
            } else {
                $this->assertStringContainsString($bynameAlias->name(), $bycriticalityAlias->name());
            }
            $count ++;
        }

        // checking that all the aliases are passed through the function
        $this->assertEquals(count($levelNamesAliases), $count);
    }
}
