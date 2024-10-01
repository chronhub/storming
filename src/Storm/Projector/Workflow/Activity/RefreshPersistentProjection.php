<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Closure;
use Storm\Projector\Workflow\Input\DiscoverEventStream;
use Storm\Projector\Workflow\Process;

final readonly class RefreshPersistentProjection
{
    use MonitorRemoteStatus;

    protected bool $onRise;

    public function __construct(private bool $onlyOnceDiscovery)
    {
        $this->onRise = false;
    }

    public function __invoke(Process $process, Closure $next): Closure|bool
    {
        /**
         * Discover the remote status which may have changed during the projection
         */
        $this->discloseRemoteStatus($process);

        /**
         * Discover event stream again which may have changed
         * after the first discovery on rising projection
         */
        if (! $this->onlyOnceDiscovery) {
            $process->call(new DiscoverEventStream);
        }

        return $next($process);
    }
}
