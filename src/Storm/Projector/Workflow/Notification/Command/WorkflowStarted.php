<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;

final class WorkflowStarted
{
    public function __invoke(AgentManager $agentRegistry): void
    {
        $agentRegistry->stat()->cycle()->next();

        $agentRegistry->time()->start();
    }
}
