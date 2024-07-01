<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Management;

use Storm\Contract\Message\DomainEvent;

final readonly class EventEmitted
{
    public function __construct(public DomainEvent $event) {}
}
