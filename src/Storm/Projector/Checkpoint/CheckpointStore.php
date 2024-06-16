<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use DateTimeImmutable;
use Storm\Contract\Projector\CheckpointRecognition;
use Storm\Contract\Projector\GapRecognition;
use Storm\Projector\Exception\CheckpointViolation;

use function array_merge;
use function in_array;
use function range;

final class CheckpointStore implements CheckpointRecognition
{
    protected array $eventStreams = [];

    public function __construct(
        private readonly CheckpointCollection $checkpoints,
        private readonly GapRecognition $gapDetector,
        private readonly GapRules $rules
    ) {
    }

    public function refreshStreams(array $eventStreams): void
    {
        $this->eventStreams = array_merge($this->eventStreams, $eventStreams);

        $this->checkpoints->onDiscover(...$eventStreams);
    }

    public function insert(string $streamName, int $streamPosition, string|DateTimeImmutable $eventTime): Checkpoint
    {
        $lastCheckpoint = $this->lastCheckpoint($streamName, $streamPosition);

        if ($this->hasNextPosition($lastCheckpoint, $streamPosition)) {
            return $this->checkpoints->next($lastCheckpoint, $streamPosition, $eventTime, null);
        }

        return $this->handleGap($lastCheckpoint, $streamPosition, $eventTime);
    }

    public function update(array $checkpoints): void
    {
        foreach ($checkpoints as $checkpoint) {
            $streamName = $checkpoint['stream_name'];

            $this->assertStreamDiscovered($streamName);

            $this->checkpoints->update($streamName, CheckpointFactory::fromArray($checkpoint));
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

    private function hasNextPosition(Checkpoint $checkpoint, int $expectedPosition): bool
    {
        return $expectedPosition === $checkpoint->position + 1;
    }

    private function handleGap(Checkpoint $lastCheckPoint, int $streamPosition, string|DateTimeImmutable $eventTime): Checkpoint
    {
        $gapType = $this->gapDetector->gapType();

        // insert a new checkpoint with a non-recoverable gap
        if (! $this->gapDetector->isRecoverable()) {
            $checkpoint = $this->newCheckpointWithGap($lastCheckPoint, $streamPosition, $eventTime, $gapType);

            return $this->checkpoints->next(
                $checkpoint, $streamPosition, $eventTime, $gapType
            );
        }

        // return a new checkpoint with a recoverable gap
        return $this->checkpoints->newCheckpoint(
            $lastCheckPoint->streamName, $streamPosition, $eventTime, $lastCheckPoint->gaps, $gapType
        );
    }

    /**
     * @throws CheckpointViolation when the stream name is not watched
     * @throws CheckpointViolation when the event position is less than 1
     * @throws CheckpointViolation when the event position is outdated
     */
    private function lastCheckpoint(string $streamName, int $eventPosition): Checkpoint
    {
        $this->assertStreamDiscovered($streamName);

        if ($eventPosition < 1) {
            throw CheckpointViolation::invalidEventPosition($streamName);
        }

        $lastCheckpoint = $this->checkpoints->last($streamName);

        if ($eventPosition < $lastCheckpoint->position) {
            throw CheckpointViolation::outdatedEventPosition($streamName);
        }

        return $lastCheckpoint;
    }

    /**
     * Insert a new checkpoint with a gap.
     *
     * @throws CheckpointViolation when the position is less than the previous position
     * @throws CheckpointViolation when the gap is already recorded
     * @throws CheckpointViolation when the gap is lower than the previous recorded gaps
     */
    private function newCheckpointWithGap(Checkpoint $checkpoint, int $position, string|DateTimeImmutable $eventTime, GapType $gapType): Checkpoint
    {
        $gaps = $this->getGapRange($checkpoint, $position);

        $this->rules
            ->mustBeGap($checkpoint, $position)
            ->shouldNotAlreadyBeRecorded($checkpoint, $gaps)
            ->mustBeGreaterThanPreviousGaps($checkpoint, $gaps);

        return $this->checkpoints->newCheckpoint(
            $checkpoint->streamName,
            $position,
            $eventTime,
            array_merge($checkpoint->gaps, $gaps),
            $gapType
        );
    }

    /**
     * Get the gap range between the last checkpoint position and the current position.
     *
     * @return array<int>|array
     */
    private function getGapRange(Checkpoint $checkpoint, int $position): array
    {
        $lastCheckpointPosition = $checkpoint->position;

        return range($lastCheckpointPosition + 1, $position - 1);
    }

    /**
     * @throws CheckpointViolation when the stream name is not watched
     */
    private function assertStreamDiscovered(string $streamName): void
    {
        if (! in_array($streamName, $this->eventStreams, true)) {
            throw CheckpointViolation::streamNotDiscovered($streamName);
        }
    }
}
