<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\CheckpointRecognition;
use Storm\Contract\Projector\GapRecognition;
use Storm\Projector\Exception\CheckpointViolation;

use function array_merge;
use function range;

final readonly class CheckpointStore implements CheckpointRecognition
{
    public function __construct(
        private CheckpointCollection $checkpoints,
        private GapRecognition $gapDetector,
        private GapRules $rules,
        private SystemClock $clock
    ) {
    }

    public function discover(string ...$streamNames): void
    {
        foreach ($streamNames as $streamName) {
            if (! $this->checkpoints->has($streamName)) {
                $checkpoint = CheckpointFactory::fromEmpty($streamName, $this->clock->generate());

                $this->checkpoints->with($checkpoint);
            }
        }
    }

    public function insert(StreamPoint $streamPoint): Checkpoint
    {
        $lastCheckpoint = $this->getLastCheckpoint($streamPoint);

        $isNextPosition = $streamPoint->position === $lastCheckpoint->position + 1;

        return $isNextPosition
            ? $this->insertNextCheckpoint($streamPoint, $lastCheckpoint->gaps)
            : $this->insertCheckpointWithNonRecoverableGap($lastCheckpoint, $streamPoint);
    }

    public function update(array $checkpoints): void
    {
        foreach ($checkpoints as $checkpoint) {
            $streamName = $checkpoint['stream_name'];

            $this->assertStreamTracked($streamName);

            $this->checkpoints->update(CheckpointFactory::fromArray($checkpoint));
        }
    }

    public function hasGap(): bool
    {
        return $this->gapDetector->hasGap();
    }

    public function sleepWhenGap(): void
    {
        $this->gapDetector->sleep();
    }

    public function resets(): void
    {
        $this->checkpoints->flush();

        $this->gapDetector->reset();
    }

    public function toArray(): array
    {
        return $this->checkpoints->all()->toArray();
    }

    public function jsonSerialize(): array
    {
        return $this->checkpoints->all()->jsonSerialize();
    }

    private function insertNextCheckpoint(StreamPoint $streamPoint, array $gaps): Checkpoint
    {
        $checkpoint = CheckpointFactory::fromStreamPoint(
            $streamPoint,
            $this->clock->generate(),
            $gaps,
            null
        );

        return $this->insertCheckpoint($checkpoint);
    }

    private function insertCheckpointWithNonRecoverableGap(Checkpoint $lastCheckPoint, StreamPoint $streamPoint): Checkpoint
    {
        $gapType = $this->gapDetector->gapType();
        $isRecoverable = $this->gapDetector->isRecoverable();
        $gaps = $lastCheckPoint->gaps;

        if (! $isRecoverable) {
            $gaps = array_merge($gaps, $this->getValidatedGaps($lastCheckPoint, $streamPoint->position));
        }

        $checkpoint = CheckpointFactory::fromStreamPoint(
            $streamPoint,
            $this->clock->generate(),
            $gaps,
            $gapType
        );

        return ! $isRecoverable ? $this->insertCheckpoint($checkpoint) : $checkpoint;
    }

    private function insertCheckpoint(Checkpoint $checkpoint): Checkpoint
    {
        $this->checkpoints->update($checkpoint);

        return $checkpoint;
    }

    /**
     * @throws CheckpointViolation when the stream name is not tracked.
     * @throws CheckpointViolation when the stream position is less than 1
     * @throws CheckpointViolation when the stream position is outdated
     */
    private function getLastCheckpoint(StreamPoint $streamPoint): Checkpoint
    {
        $this->assertStreamTracked($streamPoint->name);
        $this->assertValidStreamPosition($streamPoint->name, $streamPoint->position);

        $lastCheckpoint = $this->checkpoints->retrieve($streamPoint->name);

        if ($streamPoint->position < $lastCheckpoint->position) {
            throw CheckpointViolation::outdatedStreamPosition(
                $streamPoint->name, $streamPoint->position
            );
        }

        return $lastCheckpoint;
    }

    /**
     * Get the validated gaps between the last checkpoint position and the current position.
     *
     * @throws CheckpointViolation when the position is less than the previous position
     * @throws CheckpointViolation when the gap is already recorded
     * @throws CheckpointViolation when the gap is lower than the previous recorded gaps
     */
    private function getValidatedGaps(Checkpoint $lastCheckpoint, int $streamPosition): array
    {
        $gaps = range($lastCheckpoint->position + 1, $streamPosition - 1);

        $this->rules
            ->mustBeGap($lastCheckpoint, $streamPosition)
            ->shouldNotAlreadyBeRecorded($lastCheckpoint, $gaps)
            ->mustBeGreaterThanPreviousGaps($lastCheckpoint, $gaps);

        return $gaps;
    }

    /**
     * Check if the stream name is tracked.
     *
     * @throws CheckpointViolation when the checkpoint is not found for the stream name
     */
    private function assertStreamTracked(string $streamName): void
    {
        if (! $this->checkpoints->has($streamName)) {
            throw CheckpointViolation::checkpointNotFound($streamName);
        }
    }

    /**
     * Check if the stream position is valid.
     *
     * @throws CheckpointViolation when the stream position is less than 1
     */
    private function assertValidStreamPosition(string $streamName, int $streamPosition): void
    {
        if ($streamPosition < 1) {
            throw CheckpointViolation::invalidStreamPosition($streamName);
        }
    }
}
