<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Projector\Repository\Events\ProjectionCreated;
use Storm\Projector\Repository\Events\ProjectionDeleted;
use Storm\Projector\Repository\Events\ProjectionDeletedWithEvents;
use Storm\Projector\Repository\Events\ProjectionError;
use Storm\Projector\Repository\Events\ProjectionReset;
use Storm\Projector\Repository\Events\ProjectionRestarted;
use Storm\Projector\Repository\Events\ProjectionStarted;
use Storm\Projector\Repository\Events\ProjectionStopped;

use function array_keys;
use function array_merge;

final class EventMap
{
    private array $map = [
        ProjectionCreated::class => [],
        ProjectionStarted::class => [],
        ProjectionStopped::class => [],
        ProjectionRestarted::class => [],
        ProjectionReset::class => [],
        ProjectionDeleted::class => [],
        ProjectionDeletedWithEvents::class => [],
        ProjectionError::class => [],
    ];

    public function addListeners(string $event, array $listeners): void
    {
        $this->map[$event] = array_merge($this->map[$event], $listeners);
    }

    public function listeners(string $event): array
    {
        return $this->map[$event] ?? [];
    }

    public function events(): array
    {
        return array_keys($this->map);
    }

    public function map(): array
    {
        return $this->map;
    }
}
