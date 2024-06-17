<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Illuminate\Support\Collection;
use Storm\Projector\Exception\CheckpointViolation;

class CheckpointCollection
{
    /**
     * @var Collection<string, Checkpoint>
     */
    protected Collection $checkpoints;

    public function __construct()
    {
        $this->checkpoints = new Collection();
    }

    public function with(Checkpoint $checkpoint): void
    {
        if (! $this->has($checkpoint->streamName)) {
            $this->checkpoints->put($checkpoint->streamName, $checkpoint);
        }
    }

    /**
     * Update the checkpoint.
     *
     * @throws CheckpointViolation when the checkpoint is not found for the stream name
     */
    public function update(Checkpoint $checkpoint): void
    {
        $this->assertStreamExists($checkpoint->streamName);

        $this->checkpoints->put($checkpoint->streamName, $checkpoint);
    }

    /**
     * Get the last checkpoint for the stream name.
     *
     * @throws CheckpointViolation when the checkpoint is not found for the stream name
     */
    public function retrieve(string $streamName): Checkpoint
    {
        $this->assertStreamExists($streamName);

        return $this->checkpoints->get($streamName);
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
     * @throws CheckpointViolation when the checkpoint is not found for the stream name
     */
    protected function assertStreamExists(string $streamName): void
    {
        if (! $this->has($streamName)) {
            throw CheckpointViolation::checkpointNotFound($streamName);
        }
    }
}
