<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentRegistry;

final class WorkflowStarted
{
    public function __invoke(AgentRegistry $agentRegistry): void
    {
        $agentRegistry->report()->cycle()->next();

        $agentRegistry->time()->start();
    }
}
