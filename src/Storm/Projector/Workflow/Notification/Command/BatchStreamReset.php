<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentRegistry;

final class BatchStreamReset
{
    public function __invoke(AgentRegistry $agentRegistry): void
    {
        $agentRegistry->report()->processed()->reset();
    }
}
