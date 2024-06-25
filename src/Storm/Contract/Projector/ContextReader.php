<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Scope\EventScope;

/**
 * @template TScope of EventScope
 */
interface ContextReader extends Context
{
    /**
     * Get the callback to initialize the state.
     *
     * @return Closure():array|null
     */
    public function userState(): ?Closure;

    /**
     * Get the event handlers to be called when an event is received.
     *
     * @return Closure(TScope): void
     *
     * @throws InvalidArgumentException When reactors are not set
     */
    public function reactors(): Closure;

    /**
     * Get stream names handled by the projection.
     *
     * @return callable(EventStreamProvider): array<string|empty>
     *
     * @throws InvalidArgumentException When queries are not set
     */
    public function queries(): callable;

    /**
     * Get the query filter to filter events.
     *
     * @throws InvalidArgumentException When query filter is not set
     */
    public function queryFilter(): QueryFilter;

    /**
     * Check if projection should keep state in memory.
     * Note that user state must be initialized.
     *
     * Default to false
     *
     * @throws InvalidArgumentException When user state is not initialized
     */
    public function keepState(): bool;

    /**
     * Get the projection identifier.
     */
    public function id(): ?string;

    /**
     * Get the conditions to stop the projection.
     *
     * @return array<string, callable>
     */
    public function haltOnCallback(): array;
}
