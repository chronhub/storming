<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\PersistentActivityFactory as PersistentActivity;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Projector\Filter\LoadLimiter;
use Storm\Projector\Scope\EmitterAccess;
use Storm\Projector\Scope\ProjectorScopeFactory;
use Storm\Projector\Support\CollectStreams;
use Storm\Projector\Workflow\Activity\DispatchSignal;
use Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Storm\Projector\Workflow\Activity\HandleStreamGap;
use Storm\Projector\Workflow\Activity\LoadStreams;
use Storm\Projector\Workflow\Activity\PersistOrUpdate;
use Storm\Projector\Workflow\Activity\RefreshPersistentProjection;
use Storm\Projector\Workflow\Activity\RisePersistentProjection;
use Storm\Projector\Workflow\Process;
use Storm\Projector\Workflow\QueryFilterResolver;
use Storm\Projector\Workflow\StreamEventReactor;

use function array_map;

final readonly class EmitterActivityFactory implements PersistentActivity
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
            new EmitterAccess($process, $this->clock),
            $then,
        );

        $eventProcessor = new StreamEventReactor($factory);

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
