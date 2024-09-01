<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Closure;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Projector\Scope\AckedOnly;
use Storm\Projector\Scope\AllTrough;
use Storm\Projector\Scope\ProjectorScope;
use Storm\Projector\Stream\CollectStreams;
use Storm\Projector\Stream\Filter\LoadLimiter;
use Storm\Projector\Stream\QueryFilterResolver;
use Storm\Projector\Stream\StreamEventReactor;
use Storm\Projector\Workflow\Activity\LoadStreams;
use Storm\Projector\Workflow\Process;

use function array_map;

/**
 * @phpstan-require-implements ActivityFactory
 */
trait ProvideActivityBuilder
{
    public function __invoke(Process $process): array
    {
        return array_map(fn (callable $activity): callable => $activity(), $this->activities($process));
    }

    /**
     * Create the stream event loader.
     */
    protected function createStreamLoader(QueryFilter $queryFilter): LoadStreams
    {
        $optionLoadLimiter = $this->option->getLoadLimiter();

        $collectStreams = new CollectStreams(
            $this->chronicler,
            new QueryFilterResolver($queryFilter),
            $optionLoadLimiter ?: new LoadLimiter($optionLoadLimiter),
        );

        return new LoadStreams($collectStreams, $this->clock);
    }

    /**
     * Create the stream event reactor.
     */
    protected function createStreamEventReactor(ProjectorScope $projectorScope, array $reactors, ?Closure $then): StreamEventReactor|callable
    {
        $scopeBinding = $reactors === []
            ? new AllTrough($projectorScope, $then)
            : new AckedOnly($reactors, $projectorScope, $then);

        return new StreamEventReactor($scopeBinding);
    }
}
