<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\CheckpointRecognition;
use Storm\Projector\Checkpoint\Checkpoints;
use Storm\Projector\Checkpoint\GapRecognition;
use Storm\Projector\Checkpoint\GapRecorder;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Checkpoint\StreamPoint;

final readonly class CheckpointReckoning implements CheckpointRecognition
{
    use ProvideRecognition;

    public function __construct(
        protected Checkpoints $checkpoints,
        protected SystemClock $clock,
        private GapRecognition $gapDetector,
        private GapRecorder $gapRecorder,
    ) {}

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

        $gaps = $lastCheckPoint->gaps;

        if (! $isRecoverable) {
            $gaps = $this->gapRecorder->merge(
                $streamPoint->name,
                $lastCheckPoint->gaps,
                $lastCheckPoint->position,
                $streamPoint->position
            );
        }

        $checkpoint = $this->create($streamPoint, $gaps, $this->gapDetector->gapType());

        return $isRecoverable ? $checkpoint : $this->checkpoints->save($checkpoint);
    }
}
