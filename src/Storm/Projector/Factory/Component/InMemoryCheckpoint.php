<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Component;

use LogicException;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\CheckpointRecognition;
use Storm\Projector\Checkpoint\Checkpoints;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Checkpoint\StreamPoint;

final readonly class InMemoryCheckpoint implements CheckpointRecognition
{
    use ProvideRecognition;

    public function __construct(
        protected Checkpoints $checkpoints,
        protected SystemClock $clock,
    ) {
        if ($this->checkpoints->recordGaps) {
            throw new LogicException('In memory checkpoint cannot be used with record gaps option enabled');
        }
    }

    public function record(StreamPoint $streamPoint): Checkpoint
    {
        $this->assertStreamTracked($streamPoint->name);

        $checkpoint = $this->create($streamPoint, [], null);

        return $this->checkpoints->save($checkpoint);
    }

    public function hasGap(): bool
    {
        return false;
    }

    public function gapType(): ?GapType
    {
        return null;
    }

    /** @codeCoverageIgnore */
    public function sleepOnGap(): void {}

    public function resets(): void
    {
        $this->checkpoints->flush();
    }
}
