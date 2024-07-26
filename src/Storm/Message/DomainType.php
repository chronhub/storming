<?php

declare(strict_types=1);

namespace Storm\Message;

enum DomainType: string
{
    case COMMAND = 'command';
    case EVENT = 'event';
    case QUERY = 'query';
}
