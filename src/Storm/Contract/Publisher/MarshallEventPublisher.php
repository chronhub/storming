<?php

declare(strict_types=1);

namespace Storm\Contract\Publisher;

use Storm\Contract\Message\DomainEvent;

interface MarshallEventPublisher
{
    public function publish(DomainEvent ...$events): void;
}
