<?php

declare(strict_types=1);

namespace Horde\Log\Handler;

class ScribeOptions extends Options
{
    public bool $addNewline = false;
    public string $category = 'default';
}
