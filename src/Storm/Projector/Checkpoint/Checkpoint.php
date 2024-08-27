<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use JsonSerializable;

/**
 * @phpstan-type CheckpointArray array{
 *     stream_name: string,
 *     position: int<0, max>,
 *     event_time: string|null,
 *     created_at: string,
 *     gaps: array<positive-int|array{0: positive-int, 1: positive-int}>|array,
 *     gap_type?: string|null
 * }
 */
final readonly class Checkpoint implements JsonSerializable
{
    public function __construct(
        public string $streamName,
        public int $position,
        public ?string $eventTime,
        public string $createdAt,
        public array $gaps,
        public ?GapType $gapType = null
    ) {}

    /**
     * Determine if the checkpoint is a gap.
     */
    public function isGap(): bool
    {
        return $this->gapType !== null;
    }

    /**
     * @return CheckpointArray
     */
    public function jsonSerialize(): array
    {
        return [
            'stream_name' => $this->streamName,
            'position' => $this->position,
            'event_time' => $this->eventTime,
            'created_at' => $this->createdAt,
            'gaps' => $this->gaps,
            'gap_type' => $this->gapType?->value ?? null,
        ];
    }
}
