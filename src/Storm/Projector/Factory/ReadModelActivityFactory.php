<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\Option;
use Storm\Projector\Scope\ReadModelAccess;
use Storm\Projector\Workflow\Activity\AfterProcessing;
use Storm\Projector\Workflow\Activity\BeforeProcessing;
use Storm\Projector\Workflow\Activity\DispatchSignal;
use Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Storm\Projector\Workflow\Activity\HandleStreamGap;
use Storm\Projector\Workflow\Activity\PersistOrUpdate;
use Storm\Projector\Workflow\Activity\RefreshPersistentProjection;
use Storm\Projector\Workflow\Activity\RisePersistentProjection;
use Storm\Projector\Workflow\Process;

final readonly class ReadModelActivityFactory implements PersistentActivityFactory
{
    use ActivityBuilderTrait;

    public function __construct(
        protected Chronicler $chronicler,
        protected Option $option,
        protected SystemClock $clock,
        protected ReadModel $readModel
    ) {}

    protected function activities(Process $process): array
    {
        [$reactors, $then] = $process->context()->get()->reactors();
        $projectorScope = new ReadModelAccess($process, $this->clock, $this->readModel);
        $eventProcessor = $this->createStreamEventReactor($projectorScope, $reactors, $then);

        $streamEventLoader = $this->createStreamLoader(
            $process->context()->get()->queryFilter(),
        );

        return [
            fn (): callable => new BeforeProcessing,
            fn (): callable => new RisePersistentProjection,
            fn (): callable => $streamEventLoader,
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new HandleStreamGap,
            fn (): callable => new PersistOrUpdate,
            fn (): callable => new DispatchSignal,
            fn (): callable => new RefreshPersistentProjection($this->option->getOnlyOnceDiscovery()),
            fn (): callable => new AfterProcessing,
        ];
    }
}
