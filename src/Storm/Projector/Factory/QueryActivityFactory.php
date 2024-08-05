<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ActivityFactory;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Projector\Filter\LoadLimiter;
use Storm\Projector\Scope\ProjectorScopeFactory;
use Storm\Projector\Scope\QueryAccess;
use Storm\Projector\Support\CollectStreams;
use Storm\Projector\Workflow\Activity\DispatchSignal;
use Storm\Projector\Workflow\Activity\HandleQueryStreamGap;
use Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Storm\Projector\Workflow\Activity\LoadStreams;
use Storm\Projector\Workflow\Activity\RefreshQueryProjection;
use Storm\Projector\Workflow\Activity\RiseQueryProjection;
use Storm\Projector\Workflow\Activity\SleepForQuery;
use Storm\Projector\Workflow\Process;
use Storm\Projector\Workflow\QueryFilterResolver;
use Storm\Projector\Workflow\StreamEventReactor;

use function array_map;

final readonly class QueryActivityFactory implements ActivityFactory
{
    public function __construct(
        protected Chronicler $chronicler,
        protected ProjectionOption $option,
        protected SystemClock $clock,
    ) {}

    public function __invoke(Process $process): array
    {
        return array_map(
            fn (callable $activity): callable => $activity(),
            $this->activities($process)
        );
    }

    protected function activities(Process $process): array
    {
        [$reactors, $then] = $process->context()->get()->reactors();

        $factory = new ProjectorScopeFactory(
            $reactors,
            new QueryAccess($process, $this->clock),
            $then,
        );

        $eventProcessor = new StreamEventReactor($factory);

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

    /**
     * Create the stream event loader.
     */
    protected function createStreamLoader(QueryFilter $queryFilter): LoadStreams
    {
        $collectStreams = new CollectStreams(
            $this->chronicler,
            new LoadLimiter($this->option->getLoadLimiter()),
            new QueryFilterResolver($queryFilter)
        );

        return new LoadStreams($collectStreams, $this->clock);
    }
}
