<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\WorkflowContext;

final readonly class RefreshPersistentProjection
{
    use MonitorRemoteStatus;

    protected bool $onRise;

    public function __construct(private bool $onlyOnceDiscovery)
    {
        $this->onRise = false;
    }

    public function __invoke(WorkflowContext $workflowContext): bool
    {
        /**
         * Discover the remote status which may have changed during the projection
         */
        $this->discloseRemoteStatus($workflowContext);

        /**
         * Discover event stream again which may have changed
         * after the first discovery on rising projection
         */
        if (! $this->onlyOnceDiscovery) {
            $workflowContext->discoverEventStream();
        }

        return true;
    }
}
