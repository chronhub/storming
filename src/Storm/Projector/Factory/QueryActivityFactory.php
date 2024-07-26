<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\AgentRegistry;
use Storm\Projector\Workflow\Activity\DispatchSignal;
use Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Storm\Projector\Workflow\Activity\RefreshQueryProjection;
use Storm\Projector\Workflow\Activity\RiseQueryProjection;
use Storm\Projector\Workflow\Activity\SleepForQuery;

final readonly class QueryActivityFactory extends AbstractActivityFactory
{
    protected function activities(AgentRegistry $agentRegistry): array
    {
        $eventProcessor = $this->createStreamEventReactor($agentRegistry);

        return [
            fn (): callable => new RiseQueryProjection(),
            fn (): callable => $this->createStreamLoader($agentRegistry),
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new SleepForQuery(),
            fn (): callable => new DispatchSignal($this->option->getSignal()),
            fn (): callable => new RefreshQueryProjection($this->option->getOnlyOnceDiscovery()),
        ];
    }
}
