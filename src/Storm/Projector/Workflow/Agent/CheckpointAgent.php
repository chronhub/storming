<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Agent;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\GapRecognition;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\Checkpoints;
use Storm\Projector\Checkpoint\GapRules;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Checkpoint\StreamPoint;

final class CheckpointAgent extends AbstractCheckpointAgent
{
    public function __construct(
        Checkpoints $checkpoints,
        private readonly GapRecognition $gapDetector,
        private readonly GapRules $rules,
        SystemClock $clock,
    ) {
        $this->checkpoints = $checkpoints;
        $this->clock = $clock;
    }

    public function record(StreamPoint $streamPoint): Checkpoint
    {
        $lastCheckpoint = $this->checkpoints->get($streamPoint->name);

        $hasNextPosition = $streamPoint->position->equalsTo($lastCheckpoint->position + 1);

        if ($hasNextPosition) {
            $checkpoint = $this->create($streamPoint, $lastCheckpoint->gaps, null);

            return $this->checkpoints->save($checkpoint);
        }

        return $this->recordWhenNonRecoverableGap($streamPoint, $lastCheckpoint);
    }

    public function hasGap(): bool
    {
        return $this->gapDetector->hasGap();
    }

    public function gapType(): ?GapType
    {
        return $this->gapDetector->gapType();
    }

    public function sleepOnGap(): void
    {
        $this->gapDetector->sleep();
    }

    public function resets(): void
    {
        $this->checkpoints->flush();

        $this->gapDetector->reset();
    }

    /**
     * Record checkpoint when the stream position is not recoverable.
     */
    private function recordWhenNonRecoverableGap(StreamPoint $streamPoint, Checkpoint $lastCheckPoint): Checkpoint
    {
        $isRecoverable = $this->gapDetector->recover();
        $gapType = $this->gapDetector->gapType();

        $gaps = $lastCheckPoint->gaps;

        if (! $isRecoverable) {
            $gaps = $this->rules->mergeGaps($lastCheckPoint, $streamPoint->position);
        }

        $checkpoint = $this->create($streamPoint, $gaps, $gapType);

        return $isRecoverable ? $checkpoint : $this->checkpoints->save($checkpoint);
    }
}
