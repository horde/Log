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
 * @author  Ralf Lang <ralf.lang@ralf-lang.de>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
declare(strict_types=1);

namespace Horde\Log;

use DateTimeImmutable;
use DateTimeInterface;
use Horde\Util\HordeString;
use Stringable;

/**
 * Represents a single log message.
 *
 * @category Horde
 * @package  Log
 * @author  Ralf Lang <ralf.lang@ralf-lang.de>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
class LogMessage implements Stringable
{
    private string $message;
    private LogLevel $level;
    private DateTimeImmutable $timestamp;
    /**
     * Context may be a hash of anything, but only primitives and Stringables are expanded.
     *
     * @var mixed[]
     */
    private array $context;
    private string $formattedMessage;

    /**
     * Constructor.
     *
     * @param LogLevel $level
     * @param string $message
     * @param mixed[] $context
     */
    public function __construct(LogLevel $level, string $message, array $context = [])
    {
        $this->message = $message;
        $this->level = $level;
        if (isset($context['timestamp']) && $context['timestamp'] instanceof DateTimeInterface) {
            $this->timestamp = DateTimeImmutable::createFromInterface($context['timestamp']);
            unset($context['timestamp']);
        } else {
            $this->timestamp = new DateTimeImmutable();
        }
        $this->context = $context;
    }

    /**
     * Merge an additional context with the current context.
     *
     * On existing key, last write wins.
     *
     * @param mixed[] $context
     * @return void
     */
    public function mergeContext(array $context): void
    {
        $this->context = array_merge($this->context, $context);
    }

    /**
     * Expose context.
     *
     * @return mixed[]
     */
    public function context(): array
    {
        return $this->context;
    }

    public function message(): string
    {
        return $this->message;
    }

    /**
     * The formatted message.
     *
     * As each handler may have its own formatters, calling into this method
     * should be left to handlers and formatters.
     *
     * @internal The (preliminary) formatting result
     *
     * @return string
     */
    public function formattedMessage(): string
    {
        return $this->formattedMessage;
    }

    /**
     * Apply formatters to the message;.
     *
     * @param LogFormatter[] $formatters
     * @return string
     */
    public function formatMessage(array $formatters): string
    {
        $this->formattedMessage = $this->message;
        foreach ($formatters as $formatter) {
            $this->formattedMessage = $formatter->format($this);
        }
        return $this->formattedMessage;
    }

    public function level(): LogLevel
    {
        return $this->level;
    }

    public function timestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function __toString(): string
    {
        return $this->formattedMessage();
    }
}
