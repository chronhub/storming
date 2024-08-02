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
 */
class Metrics extends Fluent
{
    /**
     * @param positive-int $processedThreshold
     */
    public function __construct(public readonly int $processedThreshold)
    {
        parent::__construct(['main' => 0, 'processed' => 0, 'acked' => 0, 'cycle' => 0]);
    }

    public function increment(string $field): void
    {
        $this->assertFieldExists($field);

        $this->$field++;
    }

    public function reset(string $field): void
    {
        $this->assertFieldExists($field);

        $this->$field = 0;
    }

    public function isReset(string $field): bool
    {
        $this->assertFieldExists($field);

        return $this->$field === 0;
    }

    public function incrementBatchStream(): void
    {
        $this->processed++;
        $this->main++;
    }

    public function isFirstCycle(): bool
    {
        return $this->cycle === 1;
    }

    public function isBatchStreamBlank(): bool
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

    protected function assertFieldExists(string $field): void
    {
        if (! $this->__isset($field)) {
            throw new InvalidArgumentException("Field $field does not exist in Metrics");
        }
    }
}
