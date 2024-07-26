<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Events;

use Storm\Projector\Repository\ProjectionSnapshot;

final readonly class ProjectionReset
{
    public function __construct(
        public string $projectionName,
        public ProjectionSnapshot $projectionResult
    ) {}
}
