<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Throwable;

interface ReadModel
{
    /**
     * Initializes the read model.
     */
    public function initialize(): void;

    /**
     * Persist changes.
     */
    public function persist(): void;

    /**
     * Resets the read model to its initial state.
     *
     * @throws Throwable
     */
    public function reset(): void;

    /**
     * Deletes the read model.
     *
     * @throws Throwable
     */
    public function down(): void;

    /**
     * Check if the read model has been initialized.
     */
    public function isInitialized(): bool;
}
