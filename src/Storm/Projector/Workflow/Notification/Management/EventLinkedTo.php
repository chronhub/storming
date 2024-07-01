<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Management;

use Storm\Contract\Message\DomainEvent;

final readonly class EventLinkedTo
{
    public function __construct(
        public string $streamName,
        public DomainEvent $event
    ) {}
}
