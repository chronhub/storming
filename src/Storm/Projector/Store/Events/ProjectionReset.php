<?php

declare(strict_types=1);

namespace Storm\Projector\Store\Events;

use Storm\Projector\ProjectionStatus;
use Storm\Projector\Store\ProjectionSnapshot;

final readonly class ProjectionReset
{
    public function __construct(
        public string $name,
        public ProjectionStatus $status,
        public ProjectionSnapshot $snapshot
    ) {}
}
