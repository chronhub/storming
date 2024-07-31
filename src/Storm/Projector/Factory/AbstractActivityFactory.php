<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Closure;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ActivityFactory;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectorScope;
use Storm\Projector\Filter\LoadLimiter;
use Storm\Projector\Support\CollectStreams;
use Storm\Projector\Workflow\Activity\LoadStreams;
use Storm\Projector\Workflow\Process;
use Storm\Projector\Workflow\QueryFilterResolver;
use Storm\Projector\Workflow\StreamEventReactor;

use function array_map;

abstract readonly class AbstractActivityFactory implements ActivityFactory
{
    public function __construct(
        protected Chronicler $chronicler,
        protected ProjectorScope $projectorScope,
        protected ProjectionOption $option,
        protected SystemClock $clock
    ) {}

    public function __invoke(Process $process): array
    {
        return array_map(
            fn (callable $activity): callable => $activity(),
            $this->activities($process)
        );
    }

    /**
     * Create the stream event reactor.
     */
    protected function createStreamEventReactor(Closure $reactors): StreamEventReactor
    {
        return new StreamEventReactor($reactors, $this->projectorScope);
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

    /**
     * Get the projector activities.
     *
     * @return array<Closure>
     */
    abstract protected function activities(Process $process): array;
}
