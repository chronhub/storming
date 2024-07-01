<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs;

use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Checkpoint\GapType;

class CheckpointStub
{
    final public const string CREATED_AT = '2024-01-01 00:00:00';

    public function with(
        string $streamName = 'stream1',
        int $position = 1,
        ?string $eventTime = null,
        ?string $createdAt = self::CREATED_AT,
        array $gaps = [],
        ?GapType $gapType = null
    ): Checkpoint {
        return CheckpointFactory::from(
            $streamName,
            $position,
            $eventTime,
            $createdAt,
            $gaps,
            $gapType
        );
    }
}
