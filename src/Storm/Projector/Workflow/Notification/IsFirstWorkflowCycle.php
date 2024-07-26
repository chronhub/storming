<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification;

use Storm\Contract\Projector\AgentRegistry;

final class IsFirstWorkflowCycle
{
    public function __invoke(AgentRegistry $agentRegistry): bool
    {
        return $agentRegistry->report()->cycle()->isFirst();
    }
}
