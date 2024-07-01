<?php

declare(strict_types=1);

namespace Storm\Projector\Repository;

use Storm\Projector\Checkpoint\Checkpoint;

final readonly class ProjectionResult
{
    /**
     * @param array<string, Checkpoint> $checkpoints
     */
    public function __construct(
        public array $checkpoints,
        public array $userState,
    ) {}
}
