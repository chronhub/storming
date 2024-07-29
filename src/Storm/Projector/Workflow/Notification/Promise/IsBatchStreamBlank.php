<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentManager;

final class IsBatchStreamBlank
{
    public function __invoke(AgentManager $agentRegistry): bool
    {
        if ($agentRegistry->stat()->processed()->count() !== 0) {
            return false;
        }

        return $agentRegistry->stat()->acked()->count() === 0;
    }
}
