<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Agent;

use Storm\Projector\Support\AckedCounter;
use Storm\Projector\Support\CycleCounter;
use Storm\Projector\Support\MainCounter;
use Storm\Projector\Support\ProcessedCounter;

readonly class StatAgent
{
    public function __construct(
        protected MainCounter $mainCounter,
        protected ProcessedCounter $processedCounter,
        protected AckedCounter $ackedCounter,
        protected CycleCounter $cycleCounter,
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
