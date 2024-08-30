<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;
use Storm\Projector\Exception\CheckpointViolation;

/**
 * @phpstan-import-type CheckpointArray from Checkpoint
 */
final class Checkpoints implements Arrayable, Countable, JsonSerializable
{
    private Collection $collection;

    public function __construct(public readonly bool $recordGaps)
    {
        $this->collection = new Collection;
    }

    /**
     * Save the checkpoint for the given stream name.
     */
    public function save(Checkpoint $checkpoint): Checkpoint
    {
        $this->collection->put($checkpoint->streamName, $checkpoint);

        return $checkpoint;
    }

    /**
     * Update the checkpoint for the given stream name.
     *
     * @throws CheckpointViolation when the gaps are not consistent with the record gaps setting
     */
    public function refresh(Checkpoint $checkpoint): Checkpoint
    {
        $this->assertCheckpointGapConsistent($checkpoint);

        $this->collection->put($checkpoint->streamName, $checkpoint);

        return $checkpoint;
    }

    /**
     * Check if the stream exists.
     */
    public function has(string $streamName): bool
    {
        return $this->collection->has($streamName);
    }

    /**
     * Get the checkpoint for the given stream name.
     *
     * @throws CheckpointViolation when the stream is not tracked
     */
    public function get(string $streamName): Checkpoint
    {
        $checkpoint = $this->collection->get($streamName);

        if (! $checkpoint instanceof Checkpoint) {
            throw CheckpointViolation::streamNotTracked($streamName);
        }

        return $checkpoint;
    }

    /**
     * Flush the checkpoints.
     */
    public function flush(): void
    {
        $this->collection = new Collection;
    }

    /**
     * Return the checkpoints as an array.
     *
     * @return array<string, Checkpoint>
     */
    public function toArray(): array
    {
        return $this->collection->toArray();
    }

    /**
     * Return the checkpoints as an array.
     * It excludes gaps from the checkpoints if recording gaps is disabled.
     *
     * @return array<CheckpointArray>
     */
    public function jsonSerialize(): array
    {
        if (! $this->recordGaps) {
            $excludeGaps = fn (Checkpoint $checkpoint) => CheckpointFactory::noGap($checkpoint);

            return $this->collection->map($excludeGaps)->jsonSerialize();
        }

        return $this->collection->jsonSerialize();
    }

    /**
     * Return the number of checkpoints.
     */
    public function count(): int
    {
        return $this->collection->count();
    }

    /**
     * Assert the checkpoint gaps are consistent with the record gaps setting.
     */
    private function assertCheckpointGapConsistent(Checkpoint $checkpoint): void
    {
        if (! $this->recordGaps && $checkpoint->gaps !== []) {
            throw CheckpointViolation::recordingGapDisabled($checkpoint->streamName);
        }
    }
}
