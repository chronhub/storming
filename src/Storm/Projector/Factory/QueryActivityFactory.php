<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ProjectorScope;
use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Workflow\Activity\CycleObserver;
use Storm\Projector\Workflow\Activity\DispatchSignal;
use Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Storm\Projector\Workflow\Activity\RiseQueryProjection;
use Storm\Projector\Workflow\Activity\SleepForQuery;

final readonly class QueryActivityFactory extends AbstractActivityFactory
{
    protected function activities(Subscriptor $subscriptor, ProjectorScope $scope): array
    {
        $eventProcessor = $this->createStreamEventReactor($subscriptor, $scope);

        return [
            fn (): callable => new CycleObserver(),
            fn (): callable => new RiseQueryProjection(),
            fn (): callable => $this->createStreamLoader($subscriptor),
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new SleepForQuery(),
            fn (): callable => new DispatchSignal($subscriptor->option()->getSignal()),
        ];
    }
}
