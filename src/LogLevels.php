<?php
/**
 * Horde Log package
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
use Psr\Log\InvalidArgumentException;

/**
 * A list of log levels to be recognized
 * 
 * @category Horde
 * @package  Log
 * @author  Ralf Lang <lang@b1-systems.de>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
class LogLevels
{
    /**
     * The configured levels
     *
     * @var LogLevel[]
     */
    private array $levels = [];

    public function register(LogLevel $level): void
    {
        /**
         * TODO: Sanity checks, prevent registering the same name twice
         */
        $this->levels[] = $level;
    }

    public static function initWithCanonicalLevels(): self
    {
        return new self(
            [
                new LogLevel(0, 'emergency'),
                new LogLevel(1, 'alert'),
                new LogLevel(2, 'critical'),
                new LogLevel(3, 'error'),
                new LogLevel(4, 'warning'),
                new LogLevel(5, 'notice'),
                new LogLevel(6, 'info'),
                new LogLevel(7, 'debug')
            ]
        );
    }

    /**
     * Register the canonical log levels and their popular aliases
     */
    public static function initWithAliasLevels(): self
    {
        return new self(
            [
                new LogLevel(0, 'emergency'),
                new LogLevel(0, 'emerg'),
                new LogLevel(1, 'alert'),
                new LogLevel(2, 'critical'),
                new LogLevel(2, 'crit'),
                new LogLevel(3, 'error'),
                new LogLevel(3, 'err'),
                new LogLevel(4, 'warning'),
                new LogLevel(4, 'warn'),
                new LogLevel(5, 'notice'),
                new LogLevel(6, 'info'),
                new LogLevel(6, 'information'),
                new LogLevel(6, 'informational'),
                new LogLevel(7, 'debug')
            ]
        );
    }
 
    /**
     * Get a registered log level by criticality
     *
     * First match wins. No match throws exception.
     * 
     * @param int $criticality
     * @return LogLevel
     * @throws InvalidArgumentException
     */
    public function getByCriticality(int $criticality): LogLevel
    {
        foreach ($this->levels as $level)
        {
            if ($criticality === $level->criticality())
            {
                return $level;
            }
        }
        throw new InvalidArgumentException('Tried to get unregistered LogLevel by criticality: ' . $criticality );

    }

    /**
     * Get a registered log level by name or throw an exception
     *
     * Matching is case insensitive.
     * No Match throws exception;
     * 
     * @param string $name
     * @return LogLevel
     * @throws InvalidArgumentException
     */
    public function getByLevelName(string $name): LogLevel
    {
        foreach ($this->levels as $level)
        {
            if (HordeString::lower($name) == $level->name()) {
                return $level;
            }
        }
        throw new InvalidArgumentException('Tried to get unregistered LogLevel by name: ' . $name );
    }

    /**
     * Constructor
     *
     * @param LogLevel[] $levels Setup with these levels. 
     *                          Circumvents any sanity checks of register
     */
    public function __construct(array $levels = [])
    {
        $this->levels = $levels;
    }
}