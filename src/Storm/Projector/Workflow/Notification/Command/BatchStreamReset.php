<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;

final class BatchStreamReset
{
    public function __invoke(AgentManager $agentRegistry): void
    {
        $agentRegistry->stat()->processed()->reset();
    }
}
