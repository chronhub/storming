<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Events;

final readonly class ProjectionReleased
{
    public function __construct(public string $projectionName) {}
}
