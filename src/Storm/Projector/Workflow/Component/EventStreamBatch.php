<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

use Storm\Projector\Stream\Iterator\MergeStreamIterator;
use Storm\Projector\Support\ExponentialSleep;

class EventStreamBatch
{
    protected ?MergeStreamIterator $iterator = null;

    public function __construct(
        protected ExponentialSleep $sleepTime
    ) {}

    public function set(?MergeStreamIterator $iterator): void
    {
        $this->iterator = $iterator;

        $iterator !== null ? $this->sleepTime->reset() : $this->sleepTime->increment();
    }

    public function pull(): ?MergeStreamIterator
    {
        $iterator = $this->iterator;

        $this->iterator = null;

        return $iterator;
    }

    /**
     * Sleep for the current sleeping time.
     */
    public function sleep(): void
    {
        $this->sleepTime->sleep();
    }
}
