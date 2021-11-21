<?php
/**
 * Horde Log package.
 *
 * @author     Bryan Alves <bryanalves@gmail.com>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Filters
 */
declare(strict_types=1);

namespace Horde\Log\Filter;

use Horde\Log\LogFilter;
use Horde\Log\LogMessage;
use Horde\Log\LogLevel;
use Horde\Log\LogException;

/**
 * @author     Bryan Alves <bryanalves@gmail.com>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Filters
 */
class ExactLevelFilter implements LogFilter
{
    /**
     * @var int
     */
    protected int $level;
    protected ?string $name = null;

    /**
     * Filter out any log messages not equal to $level.
     *
     * @param  int  $level  Log level to pass through the filter
     * @param  string|null $name optionally also check for same level name
     */
    public function __construct(int $level, string $name = null)
    {
        if (!is_integer($level)) {
            throw new LogException('Level must be an integer');
        }

        $this->level = $level;
        $this->name = $name;
    }

    public static function constructFromLevel(LogLevel $level): self
    {
        return new self($level->criticality(), $level->name());
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param  LogMessage    $event    Log event
     * @return bool            accepted?
     */
    public function accept(LogMessage $event): bool
    {
        $loglevel = $event->level();
        if ($this->name && ($this->name != $loglevel->name())) {
            return false;
        }
        return $loglevel->criticality() == $this->level;
    }
}
