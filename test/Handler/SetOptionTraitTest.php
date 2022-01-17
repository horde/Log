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

use Horde\Log\Handler\MockHandler;
use PHPUnit\Framework\TestCase;
use Horde\Log\LogException;
use Horde\Log\Handler\Options;
use Horde\Log\Handler\SetOptionsTrait;

class SetOptionTraitTest extends TestCase
{
    public function setUp(): void
    {
        $this->setOptionsTrait = $this->getMockForTrait(SetOptionsTrait::class);
    }

    // Testing if new Options is set (without mockhandler)
    public function testNewOptionsForSetOptionsTrait(): void
    {
        $this->setOptionsTrait->options = new Options();
        $optionskey = 'ident';
        $testOptionskey = 'test';
        $this->setOptionsTrait->options->$optionskey = $testOptionskey;
        $this->assertSame($this->setOptionsTrait->options->$optionskey, $testOptionskey);
        $this->setOptionsTrait->setOption('ident', 'bla');
        $this->assertNotSame($this->setOptionsTrait->options->$optionskey, $testOptionskey);
    }

    // Testing if new Options throws correct errors (without mockhandler)
    public function testSetOptionsThrowsErrors(): void
    {
        $this->expectException(LogException::class);
        $this->setOptionsTrait->setOption('ident', 'bla'); // as there are no options set, setOptions will throw an error
    }
}
