<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentManager;

final class CurrentAckedCount
{
    /**
     * @return int<0, max>
     */
    public function __invoke(AgentManager $agentRegistry): int
    {
        return $agentRegistry->stat()->acked()->count();
    }
}
