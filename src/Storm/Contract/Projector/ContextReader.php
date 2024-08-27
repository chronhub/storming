<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Projector\Exception\ConfigurationViolation;

interface ContextReader extends Context
{
    /**
     * Get the callback to initialize the state.
     *
     * @return (Closure():array)|null
     */
    public function userState(): ?Closure;

    /**
     * Check if user state is initialized.
     */
    public function isUserStateInitialized(): bool;

    /**
     * Get the event handlers to be called when an event is received.
     *
     * @throws ConfigurationViolation When reactors are not set
     */
    public function reactors(): array;

    /**
     * Get the query to fetch streams.
     *
     * @return callable(EventStreamProvider): array<string>
     *
     * @throws ConfigurationViolation When queries are not set
     */
    public function query(): callable;

    /**
     * Get the query filter to filter events.
     *
     * @throws ConfigurationViolation When query filter is not set
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
