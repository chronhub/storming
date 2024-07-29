<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;
use Storm\Contract\Projector\EmitOnce;

final class StreamEventAckedReset implements EmitOnce
{
    public function __invoke(AgentManager $agentRegistry): void
    {
        $agentRegistry->stat()->acked()->reset();
    }
}
