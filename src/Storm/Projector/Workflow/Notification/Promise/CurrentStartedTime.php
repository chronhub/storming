<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentRegistry;

final class CurrentStartedTime
{
    public function __invoke(AgentRegistry $agentRegistry): int
    {
        return $agentRegistry->time()->getStartedTime();
    }
}
