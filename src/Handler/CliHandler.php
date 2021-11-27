<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
declare(strict_types=1);

namespace Horde\Log\Handler;

use Horde\Log\LogFilter;
use Horde\Log\LogFormatter;
use Horde\Log\LogHandler;
use Horde\Log\LogMessage;
use Horde\Log\LogException;
use Horde\Log\Formatter\CliFormatter;
use Horde_Cli;

/**
 * Logs to the command line interface using Horde_Cli.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
class CliHandler extends StreamHandler
{
    use SetOptionsTrait;
    /**
     * A CLI handler.
     *
     * @var Horde_Cli
     */
    protected $cli;
    /**
     * Options.
     *
     * @var Options
     */
    protected Options $options;

    /**
     * Class Constructor
     *
     * @param LogFormatter[]|null $formatters  Log formatters.
     * @param Horde_Cli|null $cli CLI Output object.
     */
    public function __construct(array $formatters = null, Horde_Cli $cli = null, Options $options = null)
    {
        $this->options = $options ?? new Options();
        $this->cli = $cli ?? new Horde_Cli();
        $this->formatters = is_null($formatters)
            ? [new CliFormatter($this->cli)]
            : $formatters;
    }

    /**
     * Write a message to the log.
     *
     * @param LogMessage $event  Log event.
     *
     * @return bool  True.
     * @throws LogException
     */
    public function write($event): bool
    {
        $this->cli->writeln($event->formattedMessage());
        return true;
    }
}
