<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentRegistry;

final class CurrentAckedCount
{
    /**
     * @return int<0, max>
     */
    public function __invoke(AgentRegistry $agentRegistry): int
    {
        return $agentRegistry->stat()->acked()->count();
    }
}
