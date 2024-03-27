<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use stdClass;
use Storm\Contract\Message\DomainEvent;
use Storm\Stream\StreamName;

interface StreamEventConverter
{
    public function toDomainEvent(object|iterable $streamEvents, StreamName $streamName): array|stdClass|DomainEvent;
}
