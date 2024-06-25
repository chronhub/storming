<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;
use Storm\Projector\Scope\EventScope;

/**
 * @template TScope of EventScope
 */
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
     * @see ContextReader::subscribeToStream()
     */
    public function subscribeToStream(string ...$streams): static;

    /**
     * Proxy method to set the categories.
     *
     * @see ContextReader::subscribeToCategory()
     */
    public function subscribeToCategory(string ...$categories): static;

    /**
     * Proxy method to set all streams.
     *
     * @see Context::subscribeToAll()
     */
    public function subscribeToAll(): static;

    /**
     * Proxy method to set the reactors.
     *
     * @param Closure(TScope): void $reactors
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
