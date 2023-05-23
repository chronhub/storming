<?php

namespace Storm\Contract\Report;

interface DomainType
{
    public const COMMAND = 'command';

    public const EVENT = 'event';

    public const QUERY = 'query';

    public const ALL = [
        self::COMMAND,
        self::EVENT,
        self::QUERY,
    ];
}