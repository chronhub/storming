<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Factory;

use function array_key_exists;

// todo
final class SpyOnEmittedEvent
{
    protected array $emittedEvents = [];

    public function __invoke(string $eventClass): void
    {
        ! array_key_exists($eventClass, $this->emittedEvents) ?
            $this->emittedEvents[$eventClass] = 1 :
            $this->emittedEvents[$eventClass]++;
    }
}
