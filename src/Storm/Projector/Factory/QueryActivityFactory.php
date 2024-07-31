<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Projector\Workflow\Activity\DispatchSignal;
use Storm\Projector\Workflow\Activity\HandleQueryStreamGap;
use Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Storm\Projector\Workflow\Activity\RefreshQueryProjection;
use Storm\Projector\Workflow\Activity\RiseQueryProjection;
use Storm\Projector\Workflow\Activity\SleepForQuery;
use Storm\Projector\Workflow\Process;

final readonly class QueryActivityFactory extends AbstractActivityFactory
{
    protected function activities(Process $process): array
    {
        $eventProcessor = $this->createStreamEventReactor(
            $process->context()->get()->reactors(),
        );

        $streamEventLoader = $this->createStreamLoader(
            $process->context()->get()->queryFilter(),
        );

        return [
            fn (): callable => new RiseQueryProjection(),
            fn (): callable => $streamEventLoader,
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new HandleQueryStreamGap(),
            fn (): callable => new SleepForQuery(),
            fn (): callable => new DispatchSignal(),
            fn (): callable => new RefreshQueryProjection($this->option->getOnlyOnceDiscovery()),
        ];
    }
}
