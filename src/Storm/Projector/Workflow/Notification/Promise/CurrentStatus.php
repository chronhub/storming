<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentRegistry;
use Storm\Projector\ProjectionStatus;

final class CurrentStatus
{
    public function __invoke(AgentRegistry $agentRegistry): ProjectionStatus
    {
        return $agentRegistry->status()->get();
    }
}
