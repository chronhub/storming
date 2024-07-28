<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentRegistry;

final class IsBatchStreamReset
{
    public function __invoke(AgentRegistry $agentRegistry): bool
    {
        return $agentRegistry->stat()->processed()->count() === 0;
    }
}
