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
use Storm\Projector\Workflow\Process;

final readonly class PersistentActivityFactory extends AbstractActivityFactory implements PersistentActivity
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
            fn (): callable => new RisePersistentProjection(),
            fn (): callable => $streamEventLoader,
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new HandleStreamGap(),
            fn (): callable => new PersistOrUpdate(),
            fn (): callable => new DispatchSignal(),
            fn (): callable => new RefreshPersistentProjection($this->option->getOnlyOnceDiscovery()),
        ];
    }
}
