<?php

declare(strict_types=1);

namespace Storm\Projector\Store;

use Storm\Projector\Checkpoint\Checkpoint;

/**
 * @phpstan-import-type CheckpointArray from Checkpoint
 */
final readonly class ProjectionSnapshot
{
    /**
     * @param array<Checkpoint|CheckpointArray> $checkpoint
     */
    public function __construct(
        public array $checkpoint,
        public array $userState,
    ) {}
}
