<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Workflow\HaltOn;

interface Context
{
    /**
     * Sets the optional callback to initialize the state.
     *
     * @param Closure():array $userState
     *
     * @throws InvalidArgumentException When user state is already set
     *
     * <code>
     *   $context->initialize(fn(): array => ['count' => 0]);
     * </code>
     */
    public function initialize(Closure $userState): self;

    /**
     * Subscribe to the given streams.
     *
     * @throws InvalidArgumentException When streams are already set
     * @throws InvalidArgumentException When streams are empty
     */
    public function subscribeToStream(string ...$streamNames): self;

    /**
     * Subscribe to the given partitions.
     *
     * @throws InvalidArgumentException When streams are already set
     * @throws InvalidArgumentException When streams are empty
     */
    public function subscribeToPartition(string ...$partitions): self;

    /**
     * Subscribe to all streams.
     *
     * @throws InvalidArgumentException When streams are already set
     * @throws InvalidArgumentException When streams are empty
     */
    public function subscribeToAll(): self;

    /**
     * Sets the event reactors to be called when an event is received.
     *
     * @param Closure(EventScope): void $reactors
     *
     * @throws InvalidArgumentException When reactors are already set
     */
    public function when(Closure $reactors): self;

    /**
     * Stop the projection when a condition is met.
     *
     * @param  Closure(HaltOn): HaltOn $haltOn
     * @return $this
     */
    public function haltOn(Closure $haltOn): self;

    /**
     * Sets the query filter to filter events.
     *
     * @throws InvalidArgumentException When query filter is already set
     */
    public function withQueryFilter(QueryFilter $queryFilter): self;

    /**
     * Keep the state in memory for the next run.
     *
     * Only available for query projection.
     * When not set, the state will be reset at each run.
     */
    public function withKeepState(): self;

    /**
     * Set a projection id to identify it.
     *
     * Note that a default id will be provided with the projection class name
     */
    public function withId(string $id): self;
}
