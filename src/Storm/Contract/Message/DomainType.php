<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

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
