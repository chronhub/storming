<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use DateTimeImmutable;
use Illuminate\Support\Collection;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\CheckpointViolation;

class CheckpointCollection
{
    /**
     * @var Collection<string, Checkpoint>
     */
    private Collection $checkpoints;

    public function __construct(protected readonly SystemClock $clock)
    {
        $this->checkpoints = new Collection();
    }

    public function onDiscover(string ...$streamNames): void
    {
        foreach ($streamNames as $streamName) {
            if (! $this->has($streamName)) {
                $this->insertNewCheckpoint($streamName, 0, null, [], null);
            }
        }
    }

    /**
     * Get the last checkpoint for the stream name.
     *
     * @throws CheckpointViolation when the checkpoint is not found for the stream name
     */
    public function last(string $streamName): Checkpoint
    {
        $this->assertStreamExists($streamName);

        return $this->checkpoints->get($streamName);
    }

    /**
     * Insert a new checkpoint.
     */
    public function next(Checkpoint $checkpoint, int $position, string|DateTimeImmutable $eventTime, ?GapType $gapType): Checkpoint
    {
        return $this->insertNewCheckpoint(
            $checkpoint->streamName,
            $position,
            $eventTime,
            $checkpoint->gaps,
            $gapType
        );
    }

    /**
     * Create a new checkpoint.
     */
    public function newCheckpoint(string $streamName, int $position, null|string|DateTimeImmutable $eventTime, array $gaps, ?GapType $gapType): Checkpoint
    {
        if ($eventTime === null && $position !== 0) {
            throw CheckpointViolation::invalidEventTime($streamName);
        }

        if ($eventTime instanceof DateTimeImmutable) {
            $eventTime = $this->clock->format($eventTime);
        }

        return CheckpointFactory::from(
            $streamName,
            $position,
            $eventTime,
            $this->clock->generate(),
            $gaps,
            $gapType
        );
    }

    /**
     * Update the checkpoint.
     *
     * @throws CheckpointViolation when the checkpoint is not found for the stream name
     */
    public function update(string $streamName, Checkpoint $checkpoint): void
    {
        $this->assertStreamExists($streamName);

        $this->checkpoints->put($streamName, $checkpoint);
    }

    /**
     * Check if the checkpoint exists by stream name.
     */
    public function has(string $streamName): bool
    {
        return $this->checkpoints->has($streamName);
    }

    /**
     * Flush all checkpoints.
     */
    public function flush(): void
    {
        $this->checkpoints = new Collection();
    }

    /**
     * Get all checkpoints as a collection.
     */
    public function all(): Collection
    {
        return $this->checkpoints;
    }

    /**
     * Insert a new checkpoint.
     */
    protected function insertNewCheckpoint(string $streamName, int $position, null|string|DateTimeImmutable $eventTime, array $gaps, ?GapType $gapType): Checkpoint
    {
        $checkpoint = $this->newCheckpoint($streamName, $position, $eventTime, $gaps, $gapType);

        $this->checkpoints->put($streamName, $checkpoint);

        return $checkpoint;
    }

    /**
     * @throws CheckpointViolation when the checkpoint is not found for the stream name
     */
    protected function assertStreamExists(string $streamName): void
    {
        if (! $this->has($streamName)) {
            throw CheckpointViolation::checkpointNotFound($streamName);
        }
    }
}
