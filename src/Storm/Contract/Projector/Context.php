<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Projector\Exception\ConfigurationViolation;
use Storm\Projector\Scope\ProjectorScope;
use Storm\Projector\Support\StopWhen;
use Storm\Projector\Workflow\Process;

interface Context
{
    /**
     * Sets the optional callback to initialize the state.
     *
     * @param  (Closure():array) $userState
     * @return $this
     *
     * @throws ConfigurationViolation When user state is already set
     */
    public function initialize(Closure $userState): self;

    /**
     * Subscribe to the given streams.
     *
     * @throws ConfigurationViolation When streams are already set
     * @throws ConfigurationViolation When streams are empty
     */
    public function subscribeToStream(string ...$streamNames): self;

    /**
     * Subscribe to the given partitions.
     *
     * @throws ConfigurationViolation When streams are already set
     * @throws ConfigurationViolation When streams are empty
     */
    public function subscribeToPartition(string ...$partitions): self;

    /**
     * Subscribe to all streams.
     *
     * @throws ConfigurationViolation When streams are already set
     * @throws ConfigurationViolation When streams are empty
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
     * @template TEvent of DomainEvent
     *
     * @param  array<Closure(TEvent): void>         $reactors
     * @param  (Closure(ProjectorScope): void)|null $then
     * @return $this
     *
     * @throws ConfigurationViolation When reactors are empty and then callback is null
     */
    public function when(array $reactors, ?Closure $then = null): self;

    /**
     * Stop the projection when a condition is met.
     * Method can be chained.
     *
     * @see StopWhen for some examples
     *
     * @param  Closure(Process): bool $haltOn
     * @return $this
     */
    public function haltOn(Closure $haltOn): self;

    /**
     * Sets the query filter to filter events.
     *
     * A Projection query filter is required for persistent projections.
     * Note, it also requires for query projection when you do not start from the beginning.
     *
     * @see ProjectionQueryFilter
     *
     * @return $this
     *
     * @throws ConfigurationViolation When query filter is already set
     */
    public function withQueryFilter(QueryFilter $queryFilter): self;

    /**
     * Set a projection id to identify it.
     * Note that a default id will be provided with the current projection class name.
     *
     * @return $this
     */
    public function withId(string $id): self;
}
