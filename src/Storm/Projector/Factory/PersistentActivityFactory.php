<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\PersistentActivityFactory as PersistentActivity;
use Storm\Projector\Workflow\Activity\DispatchSignal;
use Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Storm\Projector\Workflow\Activity\HandleStreamGap;
use Storm\Projector\Workflow\Activity\PersistOrUpdate;
use Storm\Projector\Workflow\Activity\RefreshPersistentProjection;
use Storm\Projector\Workflow\Activity\RisePersistentProjection;
use Storm\Projector\Workflow\WorkflowContext;

final readonly class PersistentActivityFactory extends AbstractActivityFactory implements PersistentActivity
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
            fn (): callable => new RisePersistentProjection(),
            fn (): callable => $streamEventLoader,
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new HandleStreamGap(),
            fn (): callable => new PersistOrUpdate(),
            fn (): callable => new DispatchSignal($this->option->getSignal()),
            fn (): callable => new RefreshPersistentProjection($this->option->getOnlyOnceDiscovery()),
        ];
    }
}
