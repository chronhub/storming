<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentRegistry;

final class BatchStreamIncrements
{
    public function __invoke(AgentRegistry $agentRegistry): void
    {
        $agentRegistry->stat()->processed()->increment();

        $agentRegistry->stat()->main()->increment();
    }
}
