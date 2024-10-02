<?php

declare(strict_types=1);

namespace Storm\Projector\Storage\Events;

use Storm\Projector\ProjectionStatus;

final readonly class ProjectionStarted
{
    public function __construct(
        public string $name,
        public ProjectionStatus $status,
    ) {}
}
