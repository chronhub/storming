<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentRegistry;

final readonly class EventStreamDiscovered
{
    public function __invoke(AgentRegistry $agentRegistry): void
    {
        $query = $agentRegistry->context()->get()->query();

        $eventStreams = $agentRegistry->discovery()->discover($query);
        $agentRegistry->recognition()->track(...$eventStreams);
    }
}
