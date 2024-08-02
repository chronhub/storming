<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Events;

use Storm\Projector\Repository\ProjectionSnapshot;

final readonly class ProjectionStopped
{
    public function __construct(
        public string $projectionName,
        public string $status,
        public ProjectionSnapshot $snapshot,
    ) {}
}
