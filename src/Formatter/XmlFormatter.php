<?php
/**
 * Horde Log package.
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Formatters
 */
declare(strict_types=1);

namespace Horde\Log\Formatter;

use Horde\Log\LogFormatter;
use Horde\Log\LogMessage;
use DOMDocument;
use DOMElement;
use Horde\Log\LogException;

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Formatters
 */
class XmlFormatter implements LogFormatter
{
    /**
     * Config options.
     *
     * @var string[]
     */
    protected $options = [
        'elementEntry'     => 'log',
        'elementTimestamp' => 'timestamp',
        'elementMessage'   => 'message',
        'elementLevel'     => 'level',
        'lineEnding'       => PHP_EOL,
    ];

    /**
     * Constructor.
     *
     * @param string[] $options Config Options See $options property
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Formats an event to be written by the handler.
     *
     * @param LogMessage $event  Log event.
     *
     * @return string  XML string.
     */
    public function format(LogMessage $event): string
    {
        $message = $event->formattedMessage();
        $level = $event->level()->name();
        $dom = new DOMDocument();

        $elt = $dom->appendChild(new DOMElement($this->options['elementEntry']));
        $elt->appendChild(new DOMElement($this->options['elementTimestamp'], date('c')));
        $elt->appendChild(new DOMElement($this->options['elementMessage'], $message));
        $elt->appendChild(new DOMElement($this->options['elementLevel'], $level));

        $xmlString = $dom->saveXML();
        if (!is_string($xmlString)) {
            throw new LogException('Failed to dump XML string from DOM data');
        }
        return preg_replace('/<\?xml version="1.0"( encoding="[^\"]*")?\?>\n/u', '', $xmlString) . $this->options['lineEnding'];
    }
}
