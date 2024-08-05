<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Events;

use Storm\Projector\ProjectionStatus;

final readonly class ProjectionRestarted
{
    public function __construct(
        public string $name,
        public ProjectionStatus $status,
    ) {}
}
