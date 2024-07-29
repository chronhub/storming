<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentManager;

final class IsSprintTerminated
{
    public function __invoke(AgentManager $agentRegistry): bool
    {
        return ! $agentRegistry->sprint()->inBackground()
            || ! $agentRegistry->sprint()->inProgress();
    }
}
