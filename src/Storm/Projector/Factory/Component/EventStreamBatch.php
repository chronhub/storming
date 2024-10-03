<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Component;

use Storm\Projector\Stream\Iterator\MergeStreamIterator;
use Storm\Projector\Support\ExponentialSleep;

class EventStreamBatch
{
    protected ?MergeStreamIterator $iterator = null;

    protected bool $wasEmpty = true;

    public function __construct(
        protected ExponentialSleep $sleepTime
    ) {}

    public function set(?MergeStreamIterator $iterator): void
    {
        $this->iterator = $iterator;

        if ($iterator !== null) {
            $this->wasEmpty = false;
            $this->sleepTime->reset();
        } else {
            $this->wasEmpty = true;
            $this->sleepTime->increment();
        }
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

    public function wasEmpty(): bool
    {
        return $this->wasEmpty;
    }
}
