<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Builder;

use Closure;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\Projector;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Exception\ConfigurationViolation;
use Storm\Projector\Options\Option;
use Storm\Projector\Scope\EmitterScope;
use Storm\Projector\Scope\QueryProjectorScope;
use Storm\Projector\Scope\ReadModelScope;
use Storm\Projector\Stream\Filter\ProjectionQueryFilter;
use Storm\Projector\Workflow\Process;

use function is_array;
use function is_string;
use function pcntl_async_signals;

/**
 * @template TScope of QueryProjectorScope|ReadModelScope|EmitterScope
 */
trait ProjectorBuilder
{
    /**
     * The projection connection name.
     */
    protected ?string $connectionName;

    /**
     * The projection connection
     */
    protected ?string $description = null;

    /**
     * The projection ID.
     * Available only for persistent projections.
     */
    protected ?string $projectionName = null;

    /**
     * The projection query filter.
     */
    protected null|string|ProjectionQueryFilter|QueryFilter $queryFilter = null;

    /**
     * The projection initial state.
     *
     * @var Closure(): array|null
     */
    protected ?Closure $initialState = null;

    /**
     * The projection options.
     *
     * @var array<Option::*, null|string|int|bool|array>|array
     */
    protected array $options = [];

    /**
     * The description for the projection.
     *
     * @var array<(Closure(DomainEvent): void)>
     */
    protected array $reactors = [];

    /**
     * The [then] reactors callback for the projection.
     *
     * @var (Closure(TScope): void)|null
     */
    protected ?Closure $then = null;

    /**
     * The streams to subscribe to.
     *
     * @var array<string>|array
     */
    protected array $fromStreams = [];

    /**
     * The partitions to subscribe to.
     *
     * @var array<string>|array
     */
    protected array $fromPartitions = [];

    /**
     * Subscribe to all streams.
     */
    protected bool $fromAll = false;

    /**
     * Dispatch async signals to the process.
     */
    protected bool $pcntlDispatch = false;

    /**
     * Stop the projection when the given callback returns true.
     *
     * @var array<Closure(Process): bool>
     */
    protected array $haltOn = [];

    /**
     * Set the connection to use for the projection.
     *
     * @return $this
     */
    public function connection(?string $connection): static
    {
        $this->connectionName = $connection;

        return $this;
    }

    /**
     * Set the description for the projection.
     *
     * @return $this
     */
    public function describe(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function filter(string|ProjectionQueryFilter|QueryFilter $queryFilter): static
    {
        $this->queryFilter = $queryFilter;

        return $this;
    }

    /**
     * @param  array|(Closure(): array) $initialState
     * @return $this
     */
    public function initialState(array|Closure $initialState): static
    {
        if (is_array($initialState)) {
            $initialState = fn () => $initialState;
        }

        $this->initialState = $initialState;

        return $this;
    }

    /**
     * @param  array<Option::*, null|string|int|bool|array>|array $option
     * @return $this
     */
    public function options(array $option): static
    {
        $this->options = $option;

        return $this;
    }

    /**
     * @param  Closure(Process): bool $haltOn
     * @return $this
     */
    public function haltOn(Closure $haltOn): static
    {
        $this->haltOn[] = $haltOn;

        return $this;
    }

    /**
     * @param  array<(Closure(DomainEvent): void)> $reactors
     * @return $this
     */
    public function reactors(array $reactors): static
    {
        $this->reactors = $reactors;

        return $this;
    }

    /**
     * @param  Closure(DomainEvent): void $reactor
     * @return $this
     */
    public function reactor(Closure $reactor): static
    {
        $this->reactors[] = $reactor;

        return $this;
    }

    /**
     * Enables the dispatching of signals to the process.
     * When enabled, the projection option `signal` will be set to true.
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
    public function then(Closure $then): static
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
    public function fromStreams(string ...$streams): static
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
    public function fromPartitions(string ...$partitions): static
    {
        $this->fromPartitions = $partitions;

        return $this;
    }

    /**
     * Subscribe to all streams.
     *
     * @return $this
     */
    public function fromAll(): static
    {
        $this->fromAll = true;

        return $this;
    }

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

        // Let the projector factory raises exceptions when the subscription is not valid
        return $projector;
    }

    protected function buildProjector(QueryProjector|EmitterProjector|ReadModelProjector $projector): QueryProjector|EmitterProjector|ReadModelProjector
    {
        if ($this->pcntlDispatch) {
            $this->options['signal'] = true;

            pcntl_async_signals(true);
        }

        if ($this->initialState) {
            $projector->initialize($this->initialState);
        }

        if ($this->description) {
            $projector->describe($this->description);
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
        // Get the default query filter configured
        if (! $this->queryFilter) {
            $this->queryFilter = $this->connector->connection(
                $this->getConnectionName()
            )->queryFilter();
        }

        if (is_string($this->queryFilter)) {
            $this->queryFilter = $this->app[$this->queryFilter];
        }
    }

    protected function getConnectionName(): string
    {
        return $this->connectionName ??= $this->connector->getDefaultDriver();
    }

    /**
     * Build the projector.
     *
     * @throws ConfigurationViolation when the projector is not configured correctly.
     */
    abstract public function build(): Projector;

    /**
     * Conditionally build the projector and run it.
     *
     * @throws ConfigurationViolation when the projector is not configured correctly.
     */
    abstract public function run(bool $keepRunning = false): void;
}
