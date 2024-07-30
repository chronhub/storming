<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Projector\Workflow\WorkflowContext;

final readonly class RefreshQueryProjection
{
    public function __construct(private bool $onlyOnceDiscovery) {}

    public function __invoke(WorkflowContext $workflowContext): bool
    {
        /**
         * Watch again for event streams which may have changed
         * after the first discovery on rising projection
         */
        if (! $this->onlyOnceDiscovery) {
            $workflowContext->discoverEventStream();
        }

        return true;
    }
}
