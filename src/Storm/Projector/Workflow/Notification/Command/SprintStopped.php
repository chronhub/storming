<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentRegistry;

final readonly class SprintStopped
{
    public function __invoke(AgentRegistry $agentRegistry): void
    {
        $agentRegistry->sprint()->halt();
    }
}
