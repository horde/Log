<?php

/**
 * Horde Log package
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */

declare(strict_types=1);

namespace Horde\Log\Handler;

use Horde\Log\Logger;
use Horde\Log\LogMessage;

/**
 * In-memory buffer handler for log messages.
 *
 * Stores LogMessage objects in an array and can replay them through a
 * real logger once one becomes available. This is useful for capturing
 * log messages during application bootstrap before a persistent handler
 * is configured.
 *
 * Usage:
 * <code>
 * $buffer = new BufferHandler();
 * $earlyLogger = new Logger([$buffer]);
 * $earlyLogger->info('Starting up...');
 *
 * // Later, once the real logger is ready:
 * $buffer->drain($realLogger);
 * </code>
 *
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Handlers
 */
class BufferHandler extends BaseHandler
{
    use SetOptionsTrait;

    /** @var LogMessage[] */
    private array $buffer = [];

    /**
     * Write a message to the in-memory buffer.
     *
     * @param LogMessage $event Log event.
     *
     * @return bool True.
     */
    public function write(LogMessage $event): bool
    {
        $this->buffer[] = $event;
        return true;
    }

    /**
     * Replay all buffered messages through the target logger, then clear.
     *
     * Messages are replayed in the order they were received. Each message
     * re-enters the target logger's full pipeline (global filters, handlers).
     *
     * @param Logger $target Logger to drain messages into.
     */
    public function drain(Logger $target): void
    {
        foreach ($this->buffer as $message) {
            $target->log(
                $message->level(),
                $message->message(),
                $message->context(),
            );
        }
        $this->buffer = [];
    }

    /**
     * Return the number of buffered messages.
     */
    public function count(): int
    {
        return count($this->buffer);
    }
}
