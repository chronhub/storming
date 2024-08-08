<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Console;

use Closure;
use Illuminate\Console\Command;
use Storm\Contract\Projector\MonitoringManager;
use Storm\Contract\Projector\ProjectorFactory;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Stream\Filter\ProjectionQueryFilter;
use Symfony\Component\Console\Command\SignalableCommandInterface;

use function pcntl_async_signals;

/**
 * checkMe not useful as it is
 *
 * @deprecated
 */
abstract class ReadModelProjectionCommand extends Command implements SignalableCommandInterface
{
    protected bool $dispatchSignal = true;

    public function __construct(
        protected ProjectorManagerInterface $projectorManager,
        protected MonitoringManager $monitoring,
    ) {
        parent::__construct();
    }

    protected function make(?Closure $init, array $reactors, ?Closure $then = null): ProjectorFactory
    {
        if ($this->dispatchSignal) {
            pcntl_async_signals(true);
        }

        $projector = $this->projectorManager->newReadModelProjector(
            $this->projectionName(),
            $this->readModel(),
            [],
            $this->connection()
        );

        if ($init instanceof Closure) {
            $projector->initialize($init);
        }

        return $projector
            ->filter($this->queryFilter())
            ->subscribeToStream(...$this->subscribeTo())
            ->when($reactors, $then);
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal): int
    {
        $this->info("Stopping read model projection: {$this->projectionName()}");

        $this->monitoring->monitor($this->connection())->markAsStop($this->projectionName());

        return self::SUCCESS;
    }

    abstract protected function connection(): string;

    abstract protected function readModel(): ReadModel;

    abstract protected function projectionName(): string;

    abstract protected function subscribeTo(): array;

    abstract protected function queryFilter(): ?ProjectionQueryFilter;
}
