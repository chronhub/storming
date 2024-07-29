<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;
use Storm\Contract\Projector\EmitOnce;

final class SleepOnGap implements EmitOnce
{
    public function __invoke(AgentManager $agentRegistry): void
    {
        $agentRegistry->recognition()->sleepOnGap();
    }
}
