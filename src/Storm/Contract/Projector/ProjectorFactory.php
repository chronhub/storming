<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;
use Storm\Projector\Scope\EventScope;

interface ProjectorFactory extends Projector
{
    /**
     * Proxy method to initialize the state.
     *
     * @param Closure(): array $userState
     *
     * @see ContextReader::initialize()
     */
    public function initialize(Closure $userState): static;

    /**
     * Proxy method to set the streams.
     *
     * @see Context::subscribeToStream()
     */
    public function subscribeToStream(string ...$streams): static;

    /**
     * Proxy method to set the categories.
     *
     * @see Context::subscribeToPartition()
     */
    public function subscribeToPartition(string ...$partitions): static;

    /**
     * Proxy method to set all streams.
     *
     * @see Context::subscribeToAll()
     */
    public function subscribeToAll(): static;

    /**
     * Proxy method to set the reactors.
     *
     * @param Closure(EventScope): void $reactors
     *
     * @see Context::when()
     */
    public function when(Closure $reactors): static;

    /**
     * Proxy method to set the stop watch callback.
     *
     * @see Context::haltOn()
     */
    public function haltOn(Closure $haltOn): static;

    /**
     * Proxy method to set the projector id.
     *
     * @see Context::withId()
     */
    public function describe(string $id): static;
}
