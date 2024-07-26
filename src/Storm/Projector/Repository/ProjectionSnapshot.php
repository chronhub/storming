<?php

declare(strict_types=1);

namespace Storm\Projector\Repository;

use Storm\Projector\Checkpoint\Checkpoint;

/**
 * @phpstan-import-type CheckpointArray from Checkpoint
 */
final readonly class ProjectionSnapshot
{
    /**
     * @param array<Checkpoint|CheckpointArray> $checkpoints
     */
    public function __construct(
        public array $checkpoints,
        public array $userState,
    ) {}
}
