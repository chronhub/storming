<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Exception\ProjectionAlreadyExists;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Exception\ProjectionFailed;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\Store\Data\ProjectionData;

interface ProjectionProvider
{
    /**
     * Create a new projection with the given name and status.
     *
     * @throws ProjectionAlreadyExists When a projection with the given name already exists.
     */
    public function createProjection(string $projectionName, ProjectionData $data): void;

    /**
     * Acquire a lock on a projection with the given name and status.
     *
     * @throws ProjectionNotFound       When a projection with the given name doesn't exist.
     * @throws ProjectionAlreadyRunning When a projection fails to acquire the lock.
     */
    public function acquireLock(string $projectionName, ProjectionData $data): void;

    /**
     * Update the data for an existing projection.
     *
     * @throws ProjectionNotFound When the projection with the given name doesn't exist.
     * @throws ProjectionFailed   When the projection has not acquired locked.
     * @throws ProjectionFailed   When the projection data cannot be updated.
     */
    public function updateProjection(string $projectionName, ProjectionData $data): void;

    /**
     * Delete an existing projection.
     *
     * @throws ProjectionNotFound When a projection with the given name doesn't exist.
     * @throws ProjectionFailed   When the projection failed to be deleted.
     */
    public function deleteProjection(string $projectionName): void;

    /**
     * Retrieve model for an existing projection.
     */
    public function retrieve(string $projectionName): ?ProjectionModel;

    /**
     * Filter projections by their names.
     *
     * @return array<string|empty> An array of string projection names that match the given names.
     */
    public function filterByNames(string ...$projectionNames): array;

    /**
     * Check if a projection with the given name exists.
     */
    public function exists(string $projectionName): bool;
}
