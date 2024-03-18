<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Batch;

use Storm\Contract\Projector\Subscriptor;

final readonly class BatchLoaded
{
    public function __construct(public bool $hasBatchStreams)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->batchStream()->hasLoadedStreams($this->hasBatchStreams);
    }
}
