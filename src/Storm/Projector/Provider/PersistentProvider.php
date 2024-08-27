<?php

declare(strict_types=1);

namespace Storm\Projector\Provider;

use Storm\Projector\Exception\ProjectionNotFound;
use Throwable;

interface PersistentProvider extends Provider
{
    /**
     * Mount the persistent subscription and resume the projection.
     */
    public function rise(): void;

    /**
     * Restart the projection.
     */
    public function restart(): void;

    /**
     * Synchronize the current state and positions of the projection.
     *
     * @throws ProjectionNotFound
     */
    public function synchronise(): void;

    /**
     * Set the current status of the projection.
     */
    public function disclose(): void;

    /**
     * Persist the current projection.
     */
    public function store(): void;

    /**
     * Check if the projection should update the lock.
     */
    public function shouldUpdateLock(): void;

    /**
     * Reset the projection.
     * Note it also deletes all snapshots taken for the current projection.
     *
     * @throws Throwable
     */
    public function revise(): void;

    /**
     * Delete the projection with or without emitted events.
     *
     * @throws Throwable
     */
    public function discard(bool $withEmittedEvents): void;

    /**
     * Release the projection lock.
     */
    public function freed(): void;

    /**
     * Stop the running projection.
     */
    public function close(): void;

    /**
     * Get the projection name.
     */
    public function getName(): string;
}
