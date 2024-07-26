<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentRegistry;
use Storm\Contract\Projector\EmitOnce;

final class TimeReset implements EmitOnce
{
    public function __invoke(AgentRegistry $agentRegistry): void
    {
        $agentRegistry->time()->reset();
    }
}
