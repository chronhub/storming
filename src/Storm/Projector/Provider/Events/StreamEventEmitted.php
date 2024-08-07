<?php

declare(strict_types=1);

namespace Storm\Projector\Provider\Events;

use Storm\Contract\Message\DomainEvent;

final readonly class StreamEventEmitted
{
    public function __construct(public DomainEvent $event) {}
}
