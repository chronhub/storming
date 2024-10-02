<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Component;

use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Checkpoint\CheckpointRecognition;
use Storm\Projector\Checkpoint\Checkpoints;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Checkpoint\StreamPoint;
use Storm\Projector\Exception\CheckpointViolation;

/**
 * @phpstan-require-implements CheckpointRecognition
 *
 * @property-read  Checkpoints $checkpoints
 * @property-read  SystemClock $clock
 */
trait ProvideRecognition
{
    public function track(string ...$streamNames): void
    {
        collect($streamNames)
            ->filter(fn (string $streamName) => ! $this->checkpoints->has($streamName))
            ->each(fn (string $streamName) => $this->checkpoints->save(
                CheckpointFactory::new($streamName, $this->clock->generate())
            ));
    }

    public function update(array $checkpoints): void
    {
        foreach ($checkpoints as $checkpoint) {
            $this->assertStreamTracked($checkpoint['stream_name']);

            $this->checkpoints->refresh(CheckpointFactory::fromArray($checkpoint));
        }
    }

    public function toArray(): array
    {
        return $this->checkpoints->toArray();
    }

    public function jsonSerialize(): array
    {
        return $this->checkpoints->jsonSerialize();
    }

    /**
     * Create a checkpoint from the given stream point, gaps and gap type.
     */
    protected function create(StreamPoint $streamPoint, array $gaps, ?GapType $gapType): Checkpoint
    {
        return CheckpointFactory::fromStreamPoint(
            $streamPoint,
            $this->clock->generate(),
            $gaps,
            $gapType
        );
    }

    /**
     * @throws CheckpointViolation when stream is not tracked
     */
    protected function assertStreamTracked(string $streamName): void
    {
        if (! $this->checkpoints->has($streamName)) {
            throw CheckpointViolation::streamNotTracked($streamName);
        }
    }
}
