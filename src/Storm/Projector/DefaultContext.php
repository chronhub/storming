<?php

declare(strict_types=1);

namespace Storm\Projector;

use Closure;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\Context;
use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Exception\ConfigurationViolation;
use Storm\Projector\Scope\ProjectorScope;
use Storm\Projector\Stream\Query\DiscoverAllStream;
use Storm\Projector\Stream\Query\DiscoverPartition;
use Storm\Projector\Stream\Query\DiscoverStream;
use Storm\Projector\Workflow\Process;

final class DefaultContext implements ContextReader
{
    private ?string $id = null;

    private ?QueryFilter $queryFilter = null;

    /** @var null|callable(EventStreamProvider): array<string>|array */
    private $query = null;

    /** @var (Closure(): array)|null */
    private ?Closure $userState = null;

    /**
     * @template TEvent of DomainEvent
     *
     * @var array{array<Closure(TEvent): void>|array, (Closure(ProjectorScope): void)|null}|array
     */
    private array $reactors = [];

    /**
     * @var array<(Closure(Process): bool)>|array
     */
    private array $haltOn = [];

    public function initialize(Closure $userState): self
    {
        if ($this->userState instanceof Closure) {
            throw ConfigurationViolation::message('Projection already initialized');
        }

        $this->userState = Closure::bind($userState, $this);

        return $this;
    }

    public function withQueryFilter(QueryFilter $queryFilter): self
    {
        if ($this->queryFilter instanceof QueryFilter) {
            throw ConfigurationViolation::message('Projection query filter already set');
        }

        $this->queryFilter = $queryFilter;

        return $this;
    }

    public function withId(string $id): Context
    {
        if ($this->id !== null) {
            throw ConfigurationViolation::message('Projection id already set');
        }

        $this->id = $id;

        return $this;
    }

    public function subscribeToStream(string ...$streamNames): self
    {
        $this->assertSubscriberNotSet();

        $this->query = new DiscoverStream($streamNames);

        return $this;
    }

    public function subscribeToPartition(string ...$partitions): self
    {
        $this->assertSubscriberNotSet();

        $this->query = new DiscoverPartition($partitions);

        return $this;
    }

    public function subscribeToAll(): self
    {
        $this->assertSubscriberNotSet();

        $this->query = new DiscoverAllStream();

        return $this;
    }

    public function when(array $reactors, ?Closure $then = null): self
    {
        if ($this->reactors !== []) {
            throw ConfigurationViolation::message('Projection reactors already set');
        }

        if ($reactors === [] && $then === null) {
            throw ConfigurationViolation::message('Projection reactors cannot be null when then callback is null'); // @codeCoverageIgnore
        }

        $this->reactors = [$reactors, $then];

        return $this;
    }

    public function haltOn(Closure $haltOn): self
    {
        $this->haltOn[] = $haltOn;

        return $this;
    }

    public function userState(): ?Closure
    {
        return $this->userState;
    }

    public function isUserStateInitialized(): bool
    {
        return $this->userState instanceof Closure;
    }

    public function reactors(): array
    {
        return $this->reactors
            ?? throw ConfigurationViolation::message('Projection reactors not set');
    }

    public function query(): callable
    {
        return $this->query
            ?? throw ConfigurationViolation::message('Projection subscriber not set');
    }

    public function queryFilter(): QueryFilter
    {
        return $this->queryFilter
            ?? throw ConfigurationViolation::message('Projection query filter not set');
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function haltOnCallback(): array
    {
        return $this->haltOn;
    }

    private function assertSubscriberNotSet(): void
    {
        if ($this->query !== null) {
            throw ConfigurationViolation::message('Projection subscriber already set');
        }
    }
}
