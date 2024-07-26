<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Events;

final readonly class ProjectionRestarted
{
    public function __construct(public string $projectionName) {}
}
