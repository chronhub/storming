<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use JsonSerializable;

interface ProjectionModel extends JsonSerializable
{
    /**
     * Get the projection name.
     */
    public function name(): string;

    /**
     * Get the projection position as JSON representation.
     */
    public function checkpoint(): string;

    /**
     * Get the projection state as JSON representation.
     */
    public function state(): string;

    /**
     * Get the projection status.
     */
    public function status(): string;

    /**
     * Get the projection locked until datetime.
     */
    public function lockedUntil(): ?string;
}
