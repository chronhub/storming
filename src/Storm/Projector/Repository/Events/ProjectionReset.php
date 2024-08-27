<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Events;

use Storm\Projector\ProjectionStatus;
use Storm\Projector\Repository\ProjectionSnapshot;

final readonly class ProjectionReset
{
    public function __construct(
        public string $name,
        public ProjectionStatus $status,
        public ProjectionSnapshot $snapshot
    ) {}
}
