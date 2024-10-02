<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Exception\ProjectionAlreadyExists;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Exception\ProjectionFailed;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Storage\ProjectionSnapshot;

interface Repository
{
    /**
     * Creates a new projection with the given status.
     *
     * @throws ProjectionAlreadyExists When a projection with the given name already exists.
     */
    public function create(ProjectionStatus $status): void;

    /**
     * Starts the projection by acquiring the lock.
     *
     * @throws ProjectionAlreadyRunning When another projection has already acquired the lock.
     * @throws ProjectionFailed         When the lock cannot be acquired.
     */
    public function start(ProjectionStatus $status): void;

    /**
     * Persists projection data
     *
     * @throws ProjectionFailed When projection data cannot be stored.
     */
    public function persist(ProjectionSnapshot $snapshot): void;

    /**
     * Stops the projection and store data.
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws ProjectionFailed   When projection data cannot be stored.
     */
    public function stop(ProjectionSnapshot $snapshot, ProjectionStatus $status): void;

    /**
     * Starts the projection again.
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws ProjectionFailed   When projection failed to update data.
     */
    public function startAgain(ProjectionStatus $status): void;

    /**
     * Resets projection data.
     */
    public function reset(ProjectionSnapshot $snapshot, ProjectionStatus $status): void;

    /**
     * Deletes the projection.
     *
     * @param bool $withEmittedEvents only use as a flag when dispatching internal event
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws ProjectionFailed   When projection data cannot be deleted.
     */
    public function delete(bool $withEmittedEvents): void;

    /**
     * Update lock when the lock threshold is reached
     */
    public function updateLock(): void;

    /**
     * Releases the lock for the projection.
     */
    public function release(): void;

    /**
     * Loads the projection status.
     */
    public function loadStatus(): ProjectionStatus;

    /**
     * Load the projection snapshot.
     *
     * A snapshot is a representation of the current state of the projection,
     * with the user state and checkpoint which includes the current stream position.
     * And gaps if enabled.
     *
     * @throws ProjectionNotFound When the projection does not exist.
     */
    public function loadSnapshot(): ProjectionSnapshot;

    /**
     * Checks if the projection exists.
     */
    public function exists(): bool;

    /**
     * Returns the name of the projection.
     *
     * @return non-empty-string
     */
    public function getName(): string;
}
