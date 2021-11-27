<?php

namespace Horde\Log\Handler;

use Horde\Log\LogException;

trait SetOptionsTrait
{
    /**
     * Sets an option specific to the implementation of the log handler.
     *
     * @param string $optionKey   Key name for the option to be changed.  Keys
     *                            are handler-specific.
     * @param mixed $optionValue  New value to assign to the option
     *
     * @return bool  True.
     * @throws LogException
     */
    public function setOption($optionKey, $optionValue): bool
    {
        if (!isset($this->options->$optionKey)) {
            throw new LogException('Unknown option "' . $optionKey . '".');
        }
        $this->options->$optionKey = $optionValue;

        return true;
    }
}
