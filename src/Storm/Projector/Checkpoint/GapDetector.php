<?php

declare(strict_types=1);

namespace Storm\Projector\Checkpoint;

use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Exception\RuntimeException;

use function array_key_exists;
use function count;
use function usleep;

final class GapDetector implements GapRecognition
{
    /** @var int<0, max> */
    private int $retries = 0;

    private bool $gapDetected = false;

    /**
     * @param array<int<0, max>> $retriesInMs The array of retry durations in milliseconds.
     */
    public function __construct(public readonly array $retriesInMs)
    {
        if (count($retriesInMs) < 2) {
            throw new InvalidArgumentException('At least two retries are required to match our gap detection logic');
        }
    }

    public function recover(): bool
    {
        if (! $this->hasRetry()) {
            $this->reset();

            return false;
        }

        return $this->gapDetected = true;
    }

    public function sleep(): void
    {
        if (! $this->gapDetected || ! $this->hasRetry()) {
            throw new RuntimeException('No gap detected or no retries left');
        }

        usleep($this->retriesInMs[$this->retries]);

        $this->retries++;
    }

    public function reset(): void
    {
        $this->gapDetected = false;

        $this->retries = 0;
    }

    public function gapType(): ?GapType
    {
        if (! $this->hasGap()) {
            return null;
        }

        return match ($this->retryLeft()) {
            0 => null,
            1 => GapType::UNRECOVERABLE_GAP,
            default => GapType::RECOVERABLE_GAP,
        };
    }

    public function hasGap(): bool
    {
        return $this->gapDetected;
    }

    public function hasRetry(): bool
    {
        return array_key_exists($this->retries, $this->retriesInMs);
    }

    public function retryLeft(): int
    {
        return count($this->retriesInMs) - $this->retries;
    }
}
