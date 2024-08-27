<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Illuminate\Support\Collection;
use Storm\Projector\Exception\CheckpointViolation;
use Storm\Stream\StreamPosition;

use function is_array;
use function range;

/**
 * @phpstan-type GapArray array<positive-int|array{0: positive-int, 1: positive-int}>
 */
class GapRecorder
{
    /**
     * Represents the default range threshold.
     */
    final public const int DEFAULT_RANGE_THRESHOLD = 2;

    /**
     * Merge the gaps between the last checkpoint position and the current position.
     *
     * @param  GapArray $previousGaps
     * @return GapArray
     */
    public function merge(string $streamName, array $previousGaps, int $lastCheckPointPosition, StreamPosition $streamPosition): array
    {
        $previousGaps = $this->sortGaps(collect($previousGaps));

        $this->ensureLastPositionIsMaxPositionOfPreviousGaps($previousGaps, $lastCheckPointPosition, $streamName);

        $gaps = $this->addGap($streamName, $previousGaps, $lastCheckPointPosition + 1, $streamPosition->value - 1);

        return $previousGaps->merge($gaps)->toArray();
    }

    protected function addGap(string $streamName, Collection $gaps, int $start, int $end): array
    {
        if ($start > $end) {
            throw CheckpointViolation::invalidGapPosition($streamName);
        }

        if (! $this->ensureNotAlreadyRecorded($gaps, $start, $end)) {
            throw CheckpointViolation::gapAlreadyRecorded($streamName, $start, $end);
        }

        return $this->determineGap($start, $end);
    }

    /**
     * @param Collection<GapArray> $gaps
     */
    protected function ensureNotAlreadyRecorded(Collection $gaps, int $start, int $end): bool
    {
        if ($this->contains($gaps, $start)) {
            return false;
        }

        return ! $this->contains($gaps, $end);
    }

    protected function contains(Collection $gaps, int $position): bool
    {
        return $gaps->contains(function (array|int $gap) use ($position) {
            return is_array($gap)
                ? $position >= $gap[0] && $position <= $gap[1]
                : $position === $gap;
        });
    }

    /**
     * @param Collection<GapArray> $gaps
     */
    protected function sortGaps(Collection $gaps): Collection
    {
        return $gaps->sortBy(fn (array|int $gap) => is_array($gap) ? $gap[0] : $gap);
    }

    /**
     * @param  positive-int $start
     * @param  positive-int $end
     * @return GapArray
     */
    protected function determineGap(int $start, int $end): array
    {
        return match (true) {
            $start === $end => [$start],
            ($end - $start) <= self::DEFAULT_RANGE_THRESHOLD => range($start, $end),
            default => [[$start, $end]],
        };
    }

    protected function ensureLastPositionIsMaxPositionOfPreviousGaps(Collection $previousGaps, int $lastCheckPointPosition, string $streamName): void
    {
        if ($previousGaps->isEmpty()) {
            return;
        }

        if (! $this->contains($previousGaps, $lastCheckPointPosition - 1)) {
            throw CheckpointViolation::inconsistentGaps($streamName, $lastCheckPointPosition);
        }
    }
}
