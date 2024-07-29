<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Closure;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ActivityFactory;
use Storm\Contract\Projector\AgentManager;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectorScope;
use Storm\Projector\Filter\LoadLimiter;
use Storm\Projector\Workflow\Activity\LoadStreams;
use Storm\Projector\Workflow\QueryFilterResolver;
use Storm\Projector\Workflow\StreamEventReactor;
use Storm\Stream\StreamPosition;

use function array_map;

abstract readonly class AbstractActivityFactory implements ActivityFactory
{
    public function __construct(
        protected Chronicler $chronicler,
        protected ProjectorScope $projectorScope,
        protected ProjectionOption $option,
        protected SystemClock $clock
    ) {}

    public function __invoke(AgentManager $agentRegistry): array
    {
        return array_map(
            fn (callable $activity): callable => $activity(),
            $this->activities($agentRegistry)
        );
    }

    /**
     * Create the query filter resolver.
     *
     * @return callable(string $streamName, StreamPosition $streamPosition, LoadLimiter $loadLimiter): QueryFilter
     */
    protected function createQueryFilterResolver(AgentManager $agentRegistry): callable
    {
        return new QueryFilterResolver($agentRegistry->context()->get()->queryFilter());
    }

    /**
     * Create the stream event reactor.
     */
    protected function createStreamEventReactor(AgentManager $agentRegistry): StreamEventReactor
    {
        return new StreamEventReactor(
            $agentRegistry->context()->get()->reactors(),
            $this->projectorScope,
            $this->option->getSignal()
        );
    }

    /**
     * Create the stream event loader.
     */
    protected function createStreamLoader(AgentManager $agentRegistry): LoadStreams
    {
        return new LoadStreams(
            $this->chronicler,
            $this->clock,
            new LoadLimiter($this->option->getLoadLimiter()),
            $this->createQueryFilterResolver($agentRegistry)
        );
    }

    /**
     * Get the projector activities.
     *
     * @return array<Closure>
     */
    abstract protected function activities(AgentManager $agentRegistry): array;
}
