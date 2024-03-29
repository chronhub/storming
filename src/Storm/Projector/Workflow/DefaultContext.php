<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Closure;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Projector\Context;
use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Repository\EventStream\DiscoverAllStream;
use Storm\Projector\Repository\EventStream\DiscoverCategories;
use Storm\Projector\Support\Notification\Handler\DiscoverStream;

final class DefaultContext implements ContextReader
{
    /**
     * @var ?callable(EventStreamProvider): array<string|empty>
     */
    private $query;

    private ?Closure $userState = null;

    private ?Closure $reactors = null;

    private ?QueryFilter $queryFilter = null;

    private bool $keepState = false;

    private ?string $id = null;

    private ?Closure $haltOn = null;

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

    public function withKeepState(): self
    {
        if ($this->keepState === true) {
            throw new InvalidArgumentException('Projection keep state already set');
        }

        $this->keepState = true;

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
        $this->assertQueriesNotSet();

        $this->query = new DiscoverStream($streamNames);

        return $this;
    }

    public function subscribeToCategory(string ...$categories): self
    {
        $this->assertQueriesNotSet();

        $this->query = new DiscoverCategories($categories);

        return $this;
    }

    public function subscribeToAll(): self
    {
        $this->assertQueriesNotSet();

        $this->query = new DiscoverAllStream();

        return $this;
    }

    public function when(Closure $reactors): self
    {
        if ($this->reactors !== null) {
            throw new InvalidArgumentException('Projection reactors already set');
        }

        $this->reactors = $reactors;

        return $this;
    }

    public function haltOn(Closure $haltOn): self
    {
        $this->haltOn = $haltOn;

        return $this;
    }

    public function userState(): ?Closure
    {
        return $this->userState;
    }

    public function reactors(): Closure
    {
        if ($this->reactors === null) {
            throw new InvalidArgumentException('Projection reactors not set');
        }

        return $this->reactors;
    }

    public function queries(): callable
    {
        if ($this->query === null) {
            throw new InvalidArgumentException('Projection streams all|names|categories not set');
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

    public function keepState(): bool
    {
        return $this->keepState;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function haltOnCallback(): array
    {
        if ($this->haltOn === null) {
            return [];
        }

        /** @var HaltOn $halt */
        $halt = value($this->haltOn, new HaltOn());

        return $halt->callbacks();
    }

    private function assertQueriesNotSet(): void
    {
        if ($this->query !== null) {
            throw new InvalidArgumentException('Projection streams all|names|categories already set');
        }
    }
}
