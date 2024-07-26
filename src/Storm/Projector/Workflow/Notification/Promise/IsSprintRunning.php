<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentRegistry;

final class IsSprintRunning
{
    public function __invoke(AgentRegistry $agentRegistry): bool
    {
        return $agentRegistry->sprint()->inProgress();
    }
}
