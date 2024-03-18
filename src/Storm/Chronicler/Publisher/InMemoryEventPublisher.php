<?php

declare(strict_types=1);

namespace Storm\Chronicler\Publisher;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Publisher\EventPublisher;
use Storm\Support\Facade\Report;

final class InMemoryEventPublisher implements EventPublisher
{
    private array $pendingEvents = [];

    public function __construct()
    {
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            Report::relay($event);
        }
    }

    public function record(iterable $streamEvents): void
    {
        foreach ($streamEvents as $streamEvent) {
            $this->pendingEvents[] = $streamEvent;
        }
    }

    public function pull(): iterable
    {
        $pendingEvents = $this->pendingEvents;

        $this->flush();

        return $pendingEvents;
    }

    public function flush(): void
    {
        $this->pendingEvents = [];
    }
}
