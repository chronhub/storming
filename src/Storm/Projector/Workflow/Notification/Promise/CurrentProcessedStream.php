<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentRegistry;

final class CurrentProcessedStream
{
    public function __invoke(AgentRegistry $agentRegistry): string
    {
        return $agentRegistry->processedStream()->get();
    }
}
