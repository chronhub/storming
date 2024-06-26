<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Watcher;

use Countable;
use Storm\Contract\Projector\TokenBucket;

class BatchStreamWatcher implements Countable
{
    protected int $counter;

    public function __construct(protected readonly TokenBucket $bucket)
    {
        $this->reset();
    }

    public function hasLoadedStreams(bool $hasLoadedStreams): void
    {
        $hasLoadedStreams ? $this->reset() : $this->counter++;
    }

    public function sleep(): void
    {
        $this->bucket->consume($this->counter);

        if ($this->counter >= $this->bucket->getCapacity()) {
            $this->reset();
        }
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        return $this->counter;
    }

    protected function reset(): void
    {
        $this->counter = 0;
    }
}
