<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Activity;

use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Options\Option;
use Storm\Projector\Scope\QueryAccess;
use Storm\Projector\Workflow\Activity\AfterProcessing;
use Storm\Projector\Workflow\Activity\BeforeProcessing;
use Storm\Projector\Workflow\Activity\DispatchSignal;
use Storm\Projector\Workflow\Activity\HandleQueryStreamGap;
use Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Storm\Projector\Workflow\Activity\RefreshQueryProjection;
use Storm\Projector\Workflow\Activity\RiseQueryProjection;
use Storm\Projector\Workflow\Activity\SleepForQuery;
use Storm\Projector\Workflow\Process;

final readonly class QueryActivityFactory implements ActivityFactory
{
    use ActivityBuilderTrait;

    public function __construct(
        protected Chronicler $chronicler,
        protected Option $option,
        protected SystemClock $clock,
    ) {}

    protected function activities(Process $process): array
    {
        [$reactors, $then] = $process->context()->get()->reactors();
        $projectorScope = new QueryAccess($process, $this->clock);
        $eventProcessor = $this->createStreamEventReactor($projectorScope, $reactors, $then);

        $streamEventLoader = $this->createStreamLoader(
            $process->context()->get()->queryFilter(),
        );

        return [
            fn (): callable => new BeforeProcessing,
            fn (): callable => new RiseQueryProjection,
            fn (): callable => $streamEventLoader,
            fn (): callable => new HandleStreamEvent($eventProcessor),
            fn (): callable => new HandleQueryStreamGap,
            fn (): callable => new SleepForQuery,
            fn (): callable => new DispatchSignal,
            fn (): callable => new RefreshQueryProjection($this->option->getOnlyOnceDiscovery()),
            fn (): callable => new AfterProcessing,
        ];
    }
}
