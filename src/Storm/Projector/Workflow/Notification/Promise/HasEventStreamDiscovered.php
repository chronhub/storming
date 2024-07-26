<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentRegistry;

/**
 * CheckMe not used anywhere
 */
final class HasEventStreamDiscovered
{
    public function __invoke(AgentRegistry $agentRegistry): bool
    {
        return $agentRegistry->discovery()->hasEventStreams();
    }
}
