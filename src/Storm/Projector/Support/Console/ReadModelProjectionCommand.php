<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console;

use Closure;
use Illuminate\Console\Command;
use Storm\Contract\Projector\ProjectionQueryFilter;
use Storm\Contract\Projector\ProjectorFactory;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\ReadModel;

use function pcntl_async_signals;

abstract class ReadModelProjectionCommand extends Command
{
    protected bool $dispatchSignal = true;

    public function __construct(protected ProjectorManagerInterface $projectorManager)
    {
        parent::__construct();
    }

    protected function make(Closure $reactors, ?Closure $init = null): ProjectorFactory
    {
        if ($this->dispatchSignal) {
            pcntl_async_signals(true);
        }

        $projector = $this->projectorManager->newReadModelProjector($this->projectionName(), $this->readModel());

        if ($init instanceof Closure) {
            $projector->initialize($init);
        }

        return $projector
            ->filter($this->getProjectionQueryFilter())
            ->subscribeToStream(...$this->subscribeTo())
            ->when($reactors);
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal)
    {
        $this->info("Stopping read model projection: {$this->projectionName()}");

        $this->projectorManager->monitor()->markAsStop($this->projectionName());
    }

    abstract protected function readModel(): ReadModel;

    abstract protected function projectionName(): string;

    abstract protected function subscribeTo(): array;

    abstract protected function queryFilter(): ?ProjectionQueryFilter;

    protected function getProjectionQueryFilter(): ProjectionQueryFilter
    {
        if ($this->queryFilter() !== null) {
            return $this->queryFilter();
        }

        return $this->projectorManager->queryScope()->fromIncludedPosition();
    }
}
