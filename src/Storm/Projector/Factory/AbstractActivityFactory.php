<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Projector\ActivityFactory;
use Storm\Contract\Projector\ProjectorScope;
use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Workflow\Activity\LoadStreams;
use Storm\Projector\Workflow\QueryFilterResolver;
use Storm\Projector\Workflow\StreamEventReactor;

use function array_map;

abstract readonly class AbstractActivityFactory implements ActivityFactory
{
    public function __construct(protected Chronicler $chronicler)
    {
    }

    public function __invoke(Subscriptor $subscriptor, ProjectorScope $projectorScope): array
    {
        return array_map(
            fn (callable $activity): callable => $activity(),
            $this->activities($subscriptor, $projectorScope)
        );
    }

    protected function createQueryFilterResolver(Subscriptor $subscriptor): QueryFilterResolver
    {
        return new QueryFilterResolver($subscriptor->getContext()->queryFilter());
    }

    protected function createStreamEventReactor(Subscriptor $subscriptor, ProjectorScope $projectorScope): StreamEventReactor
    {
        return new StreamEventReactor(
            $subscriptor->getContext()->reactors(),
            $projectorScope,
            $subscriptor->option()->getSignal()
        );
    }

    protected function createStreamLoader(Subscriptor $subscriptor): LoadStreams
    {
        return new LoadStreams(
            $this->chronicler,
            $subscriptor->clock(),
            $subscriptor->option()->getLoadLimiter(),
            $this->createQueryFilterResolver($subscriptor)
        );
    }

    /**
     * @return array<callable>
     */
    abstract protected function activities(Subscriptor $subscriptor, ProjectorScope $scope): array;
}
