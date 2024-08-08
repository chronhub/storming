<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Builder;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\Projector;
use Storm\Contract\Projector\ProjectorManagement;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Exception\ConfigurationViolation;
use Storm\Projector\Options\Option;
use Storm\Projector\Scope\EmitterScope;
use Storm\Projector\Scope\QueryProjectorScope;
use Storm\Projector\Scope\ReadModelScope;
use Storm\Projector\Stream\Filter\ProjectionQueryFilter;
use Storm\Projector\Workflow\Process;

use function is_string;
use function pcntl_async_signals;

/**
 * @template TScope of QueryProjectorScope|ReadModelScope|EmitterScope
 */
abstract class ProjectorBuilder
{
    private ?string $connection;

    protected ?string $description = null;

    protected ?string $projectionName = null;

    protected null|string|ProjectionQueryFilter|QueryFilter $queryFilter = null;

    /** @var Closure(): array|null */
    protected ?Closure $initialState = null;

    /** @var array<Option::*, null|string|int|bool|array>|array */
    protected array $option = [];

    /** @var array<Closure> */
    protected array $reactors = [];

    /** @var (Closure(TScope): void)|null */
    protected ?Closure $then = null;

    /** @var array<string>|array */
    protected array $fromStreams = [];

    /** @var array<string>|array */
    protected array $fromPartitions = [];

    protected bool $fromAll = false;

    protected bool $pcntlDispatch = false;

    /**
     * @var array<Closure(Process): bool>
     */
    protected array $haltOn = [];

    public function __construct(
        protected ProjectorManagement $projectorManagement,
        protected ProjectorManagerInterface $projectorManager,
        protected Application $app,
    ) {}

    /**
     * Set the connection to use for the projection.
     *
     * @return $this
     */
    public function withConnection(?string $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the description for the projection.
     *
     * @return $this
     */
    public function withDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the projection name for persistent projections only.
     *
     * @return $this
     */
    public function withProjectionName(string $projectionName): static
    {
        $this->projectionName = $projectionName;

        return $this;
    }

    public function withQueryFilter(string|ProjectionQueryFilter|QueryFilter $queryFilter): static
    {
        $this->queryFilter = $queryFilter;

        return $this;
    }

    /**
     * @param  (Closure(): array) $initialState
     * @return $this
     */
    public function withInitialState(Closure $initialState): static
    {
        $this->initialState = $initialState;

        return $this;
    }

    /**
     * @param  array<Option::*, null|string|int|bool|array>|array $option
     * @return $this
     */
    public function withOptions(array $option): static
    {
        $this->option = $option;

        return $this;
    }

    /**
     * @template TEvent of DomainEvent
     *
     * @param  array<(Closure(TEvent): void)> $reactors
     * @return $this
     */
    public function withReactors(array $reactors): static
    {
        $this->reactors = $reactors;

        return $this;
    }

    /**
     * @template TEvent of DomainEvent
     *
     * @param  Closure(TEvent): void $reactor
     * @return $this
     */
    public function withReactor(Closure $reactor): static
    {
        $this->reactors[] = $reactor;

        return $this;
    }

    /**
     * Enables the dispatching of signals to the process.
     * When enabled, the option `signal` will be set to true.
     *
     * @return $this
     */
    public function enableSignal(bool $enable): static
    {
        $this->pcntlDispatch = $enable;

        return $this;
    }

    /**
     * Set the [then] reactors callback for the projection.
     * Required when reactors are empty.
     *
     * @param  (Closure(TScope): void) $then
     * @return $this
     */
    public function withThen(Closure $then): static
    {
        $this->then = $then;

        return $this;
    }

    /**
     * Subscribe to the given streams.
     *
     * @param  array<string> $streams
     * @return $this
     */
    public function fromStreams(array $streams): static
    {
        $this->fromStreams = $streams;

        return $this;
    }

    /**
     * Subscribe to the given partitions.
     *
     * @param  array<string> $partitions
     * @return $this
     */
    public function fromPartitions(array $partitions): static
    {
        $this->fromPartitions = $partitions;

        return $this;
    }

    /**
     * Subscribe to all streams without internal streams.
     *
     * @return $this
     */
    public function fromAll(): static
    {
        $this->fromAll = true;

        return $this;
    }

    /**
     * Let the projector factory raises exceptions when the subscription is not valid.
     */
    protected function subscribeTo(QueryProjector|EmitterProjector|ReadModelProjector $projector): QueryProjector|EmitterProjector|ReadModelProjector
    {
        if ($this->fromAll) {
            return $projector->subscribeToAll();
        }

        if ($this->fromStreams !== []) {
            return $projector->subscribeToStream(...$this->fromStreams);
        }

        if ($this->fromPartitions !== []) {
            return $projector->subscribeToPartition(...$this->fromPartitions);
        }

        return $projector;
    }

    protected function buildProjector(QueryProjector|EmitterProjector|ReadModelProjector $projector): QueryProjector|EmitterProjector|ReadModelProjector
    {
        if ($this->pcntlDispatch) {
            $this->option['signal'] = true;

            // checkMe use a process to call the signal handler
            pcntl_async_signals(true);
        }

        if ($this->initialState) {
            $projector->initialize($this->initialState);
        }

        foreach ($this->haltOn as $haltOn) {
            $projector->haltOn($haltOn);
        }

        $this->configureQueryFilterIfNeeded();

        $projector
            ->filter($this->queryFilter)
            ->when($this->reactors, $this->then);

        return $this->subscribeTo($projector);
    }

    protected function configureQueryFilterIfNeeded(): void
    {
        if ($this->queryFilter === null) {
            $this->queryFilter = $this->projectorManagement->connection($this->connection)->queryFilter();
        }

        if (is_string($this->queryFilter)) {
            $this->queryFilter = $this->app[$this->queryFilter];
        }
    }

    protected function getConnection(): string
    {
        return $this->connection ??= $this->projectorManagement->getDefaultDriver();
    }

    /**
     * Build the projector.
     *
     * @throws ConfigurationViolation when the projector is not configured correctly.
     */
    abstract public function build(): Projector;

    /**
     * Run the projector.
     */
    abstract public function run(bool $keepRunning = false): void;
}
