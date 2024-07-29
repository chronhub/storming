<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentManager;

/**
 * CheckMe not used anywhere
 */
final class HasEventStreamDiscovered
{
    public function __invoke(AgentManager $agentRegistry): bool
    {
        return $agentRegistry->discovery()->hasEventStreams();
    }
}
