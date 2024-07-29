<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentManager;

final class CurrentProcessedStream
{
    public function __invoke(AgentManager $agentRegistry): string
    {
        return $agentRegistry->processedStream()->get();
    }
}
