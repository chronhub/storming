<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;

final readonly class SprintStopped
{
    public function __invoke(AgentManager $agentRegistry): void
    {
        $agentRegistry->sprint()->halt();
    }
}
