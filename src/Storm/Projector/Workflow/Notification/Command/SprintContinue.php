<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentRegistry;

final readonly class SprintContinue
{
    public function __invoke(AgentRegistry $agentRegistry): void
    {
        $agentRegistry->sprint()->continue();
    }
}
