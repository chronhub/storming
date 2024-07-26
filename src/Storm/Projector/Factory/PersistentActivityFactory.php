<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\AgentRegistry;
use Storm\Contract\Projector\PersistentActivityFactory as PersistentActivity;
use Storm\Projector\Workflow\Activity\DispatchSignal;
use Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Storm\Projector\Workflow\Activity\HandleStreamGap;
use Storm\Projector\Workflow\Activity\PersistOrUpdate;
use Storm\Projector\Workflow\Activity\RefreshPersistentProjection;
use Storm\Projector\Workflow\Activity\RisePersistentProjection;

final readonly class PersistentActivityFactory extends AbstractActivityFactory implements PersistentActivity
{
    protected function activities(AgentRegistry $agentRegistry): array
    {
        $eventProcessor = $this->createStreamEventReactor($agentRegistry);

        return [
            fn (): callable => new RisePersistentProjection(),
            fn (): callable => $this->createStreamLoader($agentRegistry),
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new HandleStreamGap(),
            fn (): callable => new PersistOrUpdate(),
            fn (): callable => new DispatchSignal($this->option->getSignal()),
            fn (): callable => new RefreshPersistentProjection($this->option->getOnlyOnceDiscovery()),
        ];
    }
}
