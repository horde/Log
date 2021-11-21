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
namespace Horde\Log\Formatter;
use Horde\Log\LogFormatter;
use Horde\Log\LogMessage;
use Horde_Cli;
use Horde\Log\LogLevel;

/**
 * Formatter for the command line interface using Horde_Cli.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Formatters
 */
class CliFormatter implements LogFormatter
{
    /**
     * A CLI handler.
     *
     * @var Horde_Cli
     */
    protected $cli;

    /**
     * Constructor.
     *
     * @param Horde_Cli $cli  A Horde_Cli instance.
     */
    public function __construct(Horde_Cli $cli)
    {
        $this->cli = $cli;
    }

    /**
     * Formats an event to be written by the handler.
     *
     * @param LogMessage $event  Log event.
     *
     * @return string  Formatted line.
     */
    public function format(LogMessage $event): string
    {
        $loglevel = $event->level();
        $flag = '['. str_pad($loglevel->name(), 7, ' ', STR_PAD_BOTH) . '] ';

        switch ($loglevel->name()) {
        case 'emergency':
        case 'alert':
        case 'critical':
        case 'crit':
        case 'error':
        case 'err':
            $type_message = $this->cli->color('red', $flag);
            break;

        case 'warn':
        case 'warning':
        case 'notice':
            $type_message = $this->cli->color('yellow', $flag);
            break;

        case 'info':
        case 'debug':
            $type_message = $this->cli->color('blue', $flag);
            break;

        default:
            $type_message = $flag;
        }

        return $type_message . $event->message();
    }

}
