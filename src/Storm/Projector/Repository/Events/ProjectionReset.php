<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Events;

use Storm\Projector\Repository\ProjectionResult;

final readonly class ProjectionReset
{
    public function __construct(
        public string $streamName,
        public ProjectionResult $projectionDetail
    ) {
    }
}
