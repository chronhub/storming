<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentManager;
use Storm\Projector\ProjectionStatus;

final class CurrentStatus
{
    public function __invoke(AgentManager $agentRegistry): ProjectionStatus
    {
        return $agentRegistry->status()->get();
    }
}
