<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Scope\ProjectorScope;
use Storm\Projector\Support\StopWhen;
use Storm\Projector\Workflow\Process;

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
     * Sets the event reactors to be called when a stream event is received.
     *
     * Empty reactors array is allowed when then callback set,
     * It is meant to acked all event streams you subscribed to,
     * with an emitter projector when emit or link to a new event stream.
     *
     * Note that all stream events will be considered as acked.
     *
     * @param array<array<(Closure(DomainEvent): void)>> $reactors
     * @param (Closure(ProjectorScope): void)|null       $then
     *
     * @throws InvalidArgumentException When reactors are already set
     * @throws InvalidArgumentException When reactors are empty and then callback is null
     */
    public function when(array $reactors, ?Closure $then = null): self;

    /**
     * Stop the projection when a condition is met.
     * Method can be chained.
     *
     * @param  Closure(Process): bool $haltOn
     * @return $this
     *
     * @see StopWhen for some examples
     */
    public function haltOn(Closure $haltOn): self;

    /**
     * Sets the query filter to filter events.
     *
     * @throws InvalidArgumentException When query filter is already set
     */
    public function withQueryFilter(QueryFilter $queryFilter): self;

    /**
     * Set a projection id to identify it.
     *
     * Note that a default id will be provided with the projection class name
     */
    public function withId(string $id): self;
}
