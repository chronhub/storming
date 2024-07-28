<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Agent;

use Storm\Projector\Support\AckedCounter;
use Storm\Projector\Support\CycleCounter;
use Storm\Projector\Support\MainCounter;
use Storm\Projector\Support\ProcessedCounter;

/**
 * @template TReport of array{
 *     projection_id: string,
 *     started_at: int<0, max>,
 *     elapsed_time: int<0, max>,
 *     ended_at: int<0, max>,
 *     cycle: int<0, max>,
 *     acked_event: int<0, max>,
 *     total_event: int<0, max>
 * }
 */
class StatAgent
{
    /**
     * @param TReport $report
     */
    protected array $report = [
        'projection_id' => '',
        'started_at' => 0,
        'elapsed_time' => 0,
        'ended_at' => 0,
        'cycle' => 0,
        'acked_event' => 0,
        'total_event' => 0,
    ];

    public function __construct(
        protected readonly MainCounter $mainCounter,
        protected readonly ProcessedCounter $processedCounter,
        protected readonly AckedCounter $ackedCounter,
        protected readonly CycleCounter $cycleCounter,
    ) {}

    /**
     * Return the main stream event counter.
     */
    public function main(): MainCounter
    {
        return $this->mainCounter;
    }

    /**
     * Return the processed stream event counter.
     */
    public function processed(): ProcessedCounter
    {
        return $this->processedCounter;
    }

    /**
     * Return the acked stream event counter.
     */
    public function acked(): AckedCounter
    {
        return $this->ackedCounter;
    }

    /**
     * Return the cycle workflow counter.
     */
    public function cycle(): CycleCounter
    {
        return $this->cycleCounter;
    }
}
