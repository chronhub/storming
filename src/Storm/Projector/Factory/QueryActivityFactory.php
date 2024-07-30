<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Projector\Workflow\Activity\DispatchSignal;
use Storm\Projector\Workflow\Activity\HandleQueryStreamGap;
use Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Storm\Projector\Workflow\Activity\RefreshQueryProjection;
use Storm\Projector\Workflow\Activity\RiseQueryProjection;
use Storm\Projector\Workflow\Activity\SleepForQuery;
use Storm\Projector\Workflow\WorkflowContext;

final readonly class QueryActivityFactory extends AbstractActivityFactory
{
    protected function activities(WorkflowContext $workflowContext): array
    {
        $eventProcessor = $this->createStreamEventReactor(
            $workflowContext->context()->get()->reactors(),
        );

        $streamEventLoader = $this->createStreamLoader(
            $workflowContext->context()->get()->queryFilter(),
        );

        return [
            fn (): callable => new RiseQueryProjection(),
            fn (): callable => $streamEventLoader,
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new HandleQueryStreamGap(),
            fn (): callable => new SleepForQuery(),
            fn (): callable => new DispatchSignal($this->option->getSignal()),
            fn (): callable => new RefreshQueryProjection($this->option->getOnlyOnceDiscovery()),
        ];
    }
}
