<?php

declare(strict_types=1);

namespace Storm\Contract\Chronicler;

use Storm\Contract\Message\DomainEvent;

interface StreamEventConverter
{
    /**
     * @return array<DomainEvent>|DomainEvent
     */
    public function toDomainEvent(object|iterable $streamEvents): array|DomainEvent;
}
