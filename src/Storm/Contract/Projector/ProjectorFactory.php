<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;

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
     * @see Context::when()
     */
    public function when(array $reactors, ?Closure $then = null): static;

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
