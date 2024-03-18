<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ProjectorScope;
use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Workflow\Activity\CycleObserver;
use Storm\Projector\Workflow\Activity\DispatchSignal;
use Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Storm\Projector\Workflow\Activity\HandleStreamGap;
use Storm\Projector\Workflow\Activity\PersistOrUpdate;
use Storm\Projector\Workflow\Activity\RefreshProjection;
use Storm\Projector\Workflow\Activity\RisePersistentProjection;

final readonly class PersistentActivityFactory extends AbstractActivityFactory
{
    protected function activities(Subscriptor $subscriptor, ProjectorScope $scope): array
    {
        $eventProcessor = $this->createStreamEventReactor($subscriptor, $scope);

        return [
            fn (): callable => new CycleObserver(),
            fn (): callable => new RisePersistentProjection(),
            fn (): callable => $this->createStreamLoader($subscriptor),
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new HandleStreamGap(),
            fn (): callable => new PersistOrUpdate(),
            fn (): callable => new DispatchSignal($subscriptor->option()->getSignal()),
            fn (): callable => new RefreshProjection($subscriptor->option()->getOnlyOnceDiscovery()),
        ];
    }
}
