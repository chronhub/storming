<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentRegistry;
use Storm\Projector\Checkpoint\Checkpoint;

final class CurrentCheckpoint
{
    /**
     * @return array<string, Checkpoint>
     */
    public function __invoke(AgentRegistry $agentRegistry): array
    {
        return $agentRegistry->recognition()->toArray();
    }
}
