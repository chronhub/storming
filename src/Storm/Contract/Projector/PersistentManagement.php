<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Exception\ProjectionNotFound;
use Throwable;

interface PersistentManagement extends Management
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
     * Update lock if it can be refreshed.
     */
    public function shouldUpdateLock(): void;

    /**
     * Persist the current projection when the threshold is reached.
     *
     * @see ProjectionOption::BLOCK_SIZE
     */
    public function persistWhenThresholdIsReached(): void;

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
