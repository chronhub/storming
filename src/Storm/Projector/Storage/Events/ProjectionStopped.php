<?php

declare(strict_types=1);

namespace Storm\Projector\Storage\Events;

use Storm\Projector\ProjectionStatus;
use Storm\Projector\Storage\ProjectionSnapshot;

final readonly class ProjectionStopped
{
    public function __construct(
        public string $name,
        public ProjectionStatus $status,
        public ProjectionSnapshot $snapshot,
    ) {}
}
