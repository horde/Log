<?php

declare(strict_types=1);

namespace Horde\Log;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Horde's Logger Interface includes PsrLoggerInterface but extends by
 * horde-specific setup and manipulation methods.
 *
 * Describes a logger instance.
 *
 * The message MUST be a string or object implementing __toString().
 *
 * The message MAY contain placeholders in the form: {foo} where foo
 * will be replaced by the context data in key "foo".
 *
 * The context array can contain arbitrary data. The only assumption that
 * can be made by implementors is that if an Exception instance is given
 * to produce a stack trace, it MUST be in a key named "exception".
 *
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * for the full interface specification.
 *
 * @author Ralf Lang <lang@b1-systems.de>
 */
interface LoggerInterface extends PsrLoggerInterface
{
    /**
     * Add a filter that will be applied before all log handlers.
     *
     * Before a message will be received by any of the handlers, it
     * must be accepted by all filters added with this method.
     *
     * @param LogFilter $filter  Filter to add.
     */
    public function addFilter(LogFilter $filter): void;
}
