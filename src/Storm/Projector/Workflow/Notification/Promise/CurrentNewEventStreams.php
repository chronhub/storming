<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentManager;

final class CurrentNewEventStreams
{
    public function __invoke(AgentManager $agentRegistry): array
    {
        return $agentRegistry->discovery()->newEventStreams();
    }
}
