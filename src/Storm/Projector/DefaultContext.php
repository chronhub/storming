<?php

declare(strict_types=1);

namespace Storm\Projector;

use Closure;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Projector\Context;
use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Scope\ProjectorScope;
use Storm\Projector\Stream\Query\DiscoverAllStream;
use Storm\Projector\Stream\Query\DiscoverPartition;
use Storm\Projector\Stream\Query\DiscoverStream;

final class DefaultContext implements ContextReader
{
    /** @var null|callable(EventStreamProvider): array<string>|array */
    private $query;

    private ?Closure $userState = null;

    /**
     * @template THandlers of array<Closure>|array
     * @template TThen of (Closure(ProjectorScope): void)|null
     *
     * @param Closure|null $reactors
     */
    private ?array $reactors = null;

    private ?QueryFilter $queryFilter = null;

    private ?string $id = null;

    /**
     * @var array<Closure>|array
     */
    private array $haltOn = [];

    public function initialize(Closure $userState): self
    {
        if ($this->userState instanceof Closure) {
            throw new InvalidArgumentException('Projection already initialized');
        }

        $this->userState = Closure::bind($userState, $this);

        return $this;
    }

    public function withQueryFilter(QueryFilter $queryFilter): self
    {
        if ($this->queryFilter instanceof QueryFilter) {
            throw new InvalidArgumentException('Projection query filter already set');
        }

        $this->queryFilter = $queryFilter;

        return $this;
    }

    public function withId(string $id): Context
    {
        if ($this->id !== null) {
            throw new InvalidArgumentException('Projection id already set');
        }

        $this->id = $id;

        return $this;
    }

    public function subscribeToStream(string ...$streamNames): self
    {
        $this->assertQueryNotSet();

        $this->query = new DiscoverStream($streamNames);

        return $this;
    }

    public function subscribeToPartition(string ...$partitions): self
    {
        $this->assertQueryNotSet();

        $this->query = new DiscoverPartition($partitions);

        return $this;
    }

    public function subscribeToAll(): self
    {
        $this->assertQueryNotSet();

        $this->query = new DiscoverAllStream();

        return $this;
    }

    public function when(array $reactors, ?Closure $then = null): self
    {
        if ($this->reactors !== null) {
            throw new InvalidArgumentException('Projection reactors already set');
        }

        if ($reactors === [] && $then === null) {
            throw new InvalidArgumentException('Projection reactors cannot be null when then callback is null');
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
        if ($this->reactors === null) {
            throw new InvalidArgumentException('Projection reactors not set');
        }

        return $this->reactors;
    }

    public function query(): callable
    {
        if ($this->query === null) {
            throw new InvalidArgumentException('Projection subscriber not set');
        }

        return $this->query;
    }

    public function queryFilter(): QueryFilter
    {
        if ($this->queryFilter === null) {
            throw new InvalidArgumentException('Projection query filter not set');
        }

        return $this->queryFilter;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function haltOnCallback(): array
    {
        return $this->haltOn;
    }

    private function assertQueryNotSet(): void
    {
        if ($this->query !== null) {
            throw new InvalidArgumentException('Projection subscriber already set');
        }
    }
}
