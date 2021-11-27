<?php

namespace Horde\Log\Handler;

class SyslogOptions extends Options
{
    public int $defaultPriority = LOG_ERR;
    public int $facility = LOG_USER;
    public int $openLogOptions = LOG_ODELAY;
}
