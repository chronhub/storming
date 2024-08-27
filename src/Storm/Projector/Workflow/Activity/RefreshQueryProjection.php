<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\Input\DiscoverEventStream;
use Storm\Projector\Workflow\Process;

final readonly class RefreshQueryProjection
{
    public function __construct(private bool $onlyOnceDiscovery) {}

    public function __invoke(Process $process): void
    {
        if (! $this->onlyOnceDiscovery) {
            $process->call(new DiscoverEventStream());
        }
    }
}
