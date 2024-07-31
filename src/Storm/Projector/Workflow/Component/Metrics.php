<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

use Illuminate\Support\Fluent;
use Storm\Projector\Exception\InvalidArgumentException;

/**
 * @property int<0, max> $main
 * @property int<0, max> $processed
 * @property int<0, max> $acked
 * @property int<0, max> $cycle
 *
 * checkMe do we need to add methods increment, reset, etc
 *  would be easier to locate the code
 */
class Metrics extends Fluent
{
    /**
     * @param positive-int $processedThreshold
     */
    public function __construct(public readonly int $processedThreshold)
    {
        /** @phpstan-ignore-next-line */
        if ($processedThreshold < 1) {
            throw new InvalidArgumentException('Processed threshold must be greater than 0');
        }

        parent::__construct(['main' => 0, 'processed' => 0, 'acked' => 0, 'cycle' => 0]);
    }

    public function isFirstCycle(): bool
    {
        return $this->cycle === 1;
    }

    public function IsBatchStreamBlank(): bool
    {
        if ($this->main !== 0) {
            return false;
        }

        return $this->acked === 0;
    }

    public function isProcessedThresholdReached(): bool
    {
        return $this->processed >= $this->processedThreshold;
    }

    public function incrementBatchStream(): void
    {
        $this->processed++;

        $this->main++;
    }
}
