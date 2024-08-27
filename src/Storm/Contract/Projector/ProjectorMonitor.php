<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Exception\ProjectionFailed;
use Storm\Projector\Exception\ProjectionNotFound;

interface ProjectorMonitor
{
    /**
     * Mark the projection as stopped.
     *
     * @throws ProjectionNotFound when projection is not found
     * @throws ProjectionFailed   when update projection status failed
     */
    public function markAsStop(string $projectionName): void;

    /**
     * Mark the projection as reset.
     *
     * @throws ProjectionNotFound when projection is not found
     * @throws ProjectionFailed   when update projection status failed
     */
    public function markAsReset(string $projectionName): void;

    /**
     * Mark the projection as deleted with or without emitted events.
     *
     * @throws ProjectionNotFound when projection is not found
     * @throws ProjectionFailed   when update projection status failed
     */
    public function markAsDelete(string $projectionName, bool $withEmittedEvents): void;

    /**
     * Get the projection status.
     *
     * @throws ProjectionNotFound
     */
    public function statusOf(string $projectionName): string;

    /**
     * Get the projection stream positions.
     *
     * @return array<string, int<0, max>>
     *
     * @throws ProjectionNotFound
     */
    public function checkpointOf(string $projectionName): array;

    /**
     * Get the projection state.
     *
     * @throws ProjectionNotFound
     */
    public function stateOf(string $projectionName): array;

    /**
     * Filter projection names which exist.
     *
     * @return array<string>
     */
    public function filterNames(string ...$streamNames): array;

    /**
     * Check if the projection exists.
     */
    public function exists(string $projectionName): bool;
}
