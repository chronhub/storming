<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Scope\EventScope;

interface ContextReader extends Context
{
    /**
     * Get the callback to initialize the state.
     *
     * @return Closure():array|null
     */
    public function userState(): ?Closure;

    /**
     * Check if user state is initialized.
     */
    public function isUserStateInitialized(): bool;

    /**
     * Get the event handlers to be called when an event is received.
     *
     * @return Closure(EventScope): void
     *
     * @throws InvalidArgumentException When reactors are not set
     */
    public function reactors(): Closure;

    /**
     * Get the query to fetch streams.
     *
     * @return callable(EventStreamProvider): array<string>
     *
     * @throws InvalidArgumentException When queries are not set
     */
    public function query(): callable;

    /**
     * Get the query filter to filter events.
     *
     * @throws InvalidArgumentException When query filter is not set
     */
    public function queryFilter(): QueryFilter;

    /**
     * Get the projection identifier.
     */
    public function id(): ?string;

    /**
     * Get the conditions to stop the projection.
     *
     * @return array<callable>
     */
    public function haltOnCallback(): array;
}
