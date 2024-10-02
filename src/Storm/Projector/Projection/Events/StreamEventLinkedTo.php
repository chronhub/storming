<?php

declare(strict_types=1);

namespace Storm\Projector\Projection\Events;

use Storm\Contract\Message\DomainEvent;

final readonly class StreamEventLinkedTo
{
    public function __construct(
        public string $streamName,
        public DomainEvent $event
    ) {}
}
