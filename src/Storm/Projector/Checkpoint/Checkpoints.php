<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Storm\Projector\Exception\CheckpointViolation;

use function array_key_exists;
use function array_map;
use function count;

/**
 * @phpstan-import-type CheckpointArray from Checkpoint
 */
final class Checkpoints implements Arrayable, Countable, JsonSerializable
{
    private array $saves;

    public function __construct(
        public readonly bool $recordGaps
    ) {
        $this->saves = [];
    }

    /**
     * Save the checkpoint for the given stream name.
     */
    public function save(Checkpoint $checkpoint): Checkpoint
    {
        $this->saves[$checkpoint->streamName] = $checkpoint;

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

        $this->saves[$checkpoint->streamName] = $checkpoint;

        return $checkpoint;
    }

    /**
     * Check if the stream exists.
     */
    public function has(string $streamName): bool
    {
        return array_key_exists($streamName, $this->saves);
    }

    /**
     * Get the checkpoint for the given stream name.
     *
     * @throws CheckpointViolation when the stream is not tracked
     */
    public function get(string $streamName): Checkpoint
    {
        $checkpoint = $this->saves[$streamName] ?? null;

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
        $this->saves = [];
    }

    /**
     * Return the checkpoints as an array.
     *
     * @return array<string, Checkpoint>
     */
    public function toArray(): array
    {
        return $this->saves;
    }

    /**
     * Return the checkpoints as an array.
     * It excludes gaps from the checkpoints if recording gaps is disabled.
     *
     * @return array<CheckpointArray>
     */
    public function jsonSerialize(): array
    {
        $saves = $this->saves;

        if (! $this->recordGaps) {
            $saves = array_map(
                fn (Checkpoint $checkpoint) => CheckpointFactory::noGap($checkpoint),
                $saves
            );
        }

        return array_map(fn (Checkpoint $checkpoint) => $checkpoint->jsonSerialize(), $saves);
    }

    /**
     * Return the number of checkpoints.
     */
    public function count(): int
    {
        return count($this->saves);
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
