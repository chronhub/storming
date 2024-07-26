<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentRegistry;

final class CurrentMainCounter
{
    public function __invoke(AgentRegistry $agentRegistry): int
    {
        return $agentRegistry->report()->main()->current();
    }
}
