<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentManager;

final class CurrentTime
{
    public function __invoke(AgentManager $agentRegistry): int
    {
        return $agentRegistry->time()->getCurrentTimestamp();
    }
}
