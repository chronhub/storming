<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentManager;
use Storm\Projector\Checkpoint\GapType;

final class CurrentGapType
{
    public function __invoke(AgentManager $agentRegistry): ?GapType
    {
        return $agentRegistry->recognition()->gapType();
    }
}
