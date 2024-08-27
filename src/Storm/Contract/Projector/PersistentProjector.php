<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface PersistentProjector extends PersistentProjectorFactory
{
    /**
     * Reset the projection.
     */
    public function reset(): void;

    /**
     * Delete the projection.
     */
    public function delete(bool $deleteEmittedEvents): void;

    /**
     * Get the projection name.
     *
     * Projection name is a substitute for stream name
     */
    public function getName(): string;
}
