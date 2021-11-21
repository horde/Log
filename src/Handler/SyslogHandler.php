<?php
/**
 * Horde Log package
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
declare(strict_types=1);
namespace Horde\Log\Handler;
use Horde\Log\Filter;
use Horde\Log\LogHandler;
use Horde\Log\LogMessage;
use Horde\Log\LogException;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
class SyslogHandler extends BaseHandler
{
    /**
     * Options to be set by setOption().
     * Sets openlog and syslog options.
     *
     * @var mixed[]
     */
    protected $options = array(
        'defaultPriority'  => LOG_ERR,
        'facility'         => LOG_USER,
        'ident'            => false,
        'openlogOptions'   => false
    );

    /**
     * Last ident set by a syslog-handler instance.
     *
     * @var string
     */
    protected $lastIdent;

    /**
     * Last facility name set by a syslog-handler instance.
     *
     * @var string
     */
    protected $lastFacility;

    /**
     * Write a message to the log.
     *
     * @param LogMessage $event  Log event.
     *
     * @return bool  True.
     * @throws LogException
     */
    public function write(LogMessage $event): bool
    {
        if (($this->options['ident'] !== $this->lastIdent) ||
            ($this->options['facility'] !== $this->lastFacility)) {
            $this->initializeSyslog();
        }

        $priority = $event->level()->criticality();
        if (!syslog($priority, $event->formattedMessage())) {
            throw new LogException('Unable to log message');
        }
        return true;
    }

    /**
     * Initialize syslog / set ident and facility.
     *
     * @throws LogException
     */
    protected function initializeSyslog(): void
    {
        $this->lastIdent = $this->options['ident'];
        $this->lastFacility = $this->options['facility'];

        if (!openlog($this->options['ident'], $this->options['openlogOptions'], $this->options['facility'])) {
            throw new LogException('Unable to open syslog');
        }
    }

}
