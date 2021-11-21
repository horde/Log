<?php
/**
 * Horde Log package.
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @category Horde
 * @package  Log
 * @author  Ralf Lang <lang@b1-systems.de>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
declare(strict_types=1);

namespace Horde\Log;

use Horde\Util\HordeString;
use Psr\Log\LogLevel as PsrLogLevel;

/**
 * Represents a single log level.
 *
 * @category Horde
 * @package  Log
 * @author  Ralf Lang <lang@b1-systems.de>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
class LogLevel extends PsrLogLevel
{
    private int $criticality;
    private string $name;

    public function __construct(int $criticality, string $name)
    {
        $this->criticality = $criticality;
        $this->name = HordeString::lower($name);
    }

    public function criticality(): int
    {
        return $this->criticality;
    }

    public function name(): string
    {
        return $this->name;
    }
}
