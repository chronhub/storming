<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentRegistry;

final class GetProjectionReport
{
    public function __invoke(AgentRegistry $agentRegistry): array
    {
        return $agentRegistry->report()->getReport();
    }
}
