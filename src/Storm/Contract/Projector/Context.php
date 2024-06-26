<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Workflow\HaltOn;

/**
 * @template TScope of EventScope
 */
interface Context
{
    /**
     * Sets the optional callback to initialize the state.
     *
     * @param Closure():array $userState
     *
     * @throws InvalidArgumentException When user state is already set
     *
     * @example $context->initialize(fn(): array => ['count' => 0]);
     */
    public function initialize(Closure $userState): self;

    /**
     * Sets the streams to fetch events from.
     *
     * @throws InvalidArgumentException When streams are already set
     * @throws InvalidArgumentException When streams are empty
     */
    public function subscribeToStream(string ...$streamNames): self;

    /**
     * Sets the categories to fetch events from.
     *
     * @throws InvalidArgumentException When streams are already set
     * @throws InvalidArgumentException When streams are empty
     */
    public function subscribeToCategory(string ...$categories): self;

    /**
     * Sets to fetch events from all streams
     *
     * @throws InvalidArgumentException When streams are already set
     * @throws InvalidArgumentException When streams are empty
     */
    public function subscribeToAll(): self;

    /**
     * Sets the event handlers to be called when an event is received.
     *
     * @param Closure(TScope): void $reactors
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
     * Also, user state must be initialized.
     */
    public function withKeepState(): self;

    /**
     * Set a projection id to identify it.
     *
     * Note that a default id will be provided but uniqueness is not guaranteed
     */
    public function withId(string $id): self;
}
