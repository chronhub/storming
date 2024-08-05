<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Closure;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Projector\ActivityFactory;
use Storm\Contract\Projector\ProjectorScope;
use Storm\Projector\Filter\LoadLimiter;
use Storm\Projector\Scope\ProjectorScopeFactory;
use Storm\Projector\Support\CollectStreams;
use Storm\Projector\Workflow\Activity\LoadStreams;
use Storm\Projector\Workflow\Process;
use Storm\Projector\Workflow\QueryFilterResolver;
use Storm\Projector\Workflow\StreamEventReactor;

use function array_map;

/**
 * @phpstan-require-implements ActivityFactory
 */
trait ProvideActivityBuilder
{
    public function __invoke(Process $process): array
    {
        return array_map(
            fn (callable $activity): callable => $activity(),
            $this->activities($process)
        );
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
     * Create the stream event reactor.
     */
    protected function createStreamEventReactor(ProjectorScope $projectorScope, array $reactors, ?Closure $then): StreamEventReactor|callable
    {
        $factory = new ProjectorScopeFactory(
            $reactors,
            $projectorScope,
            $then,
        );

        return new StreamEventReactor($factory);
    }
}
