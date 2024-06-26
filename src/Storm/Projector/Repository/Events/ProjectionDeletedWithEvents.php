<?php

declare(strict_types=1);

namespace Storm\Projector\Repository\Events;

final readonly class ProjectionDeletedWithEvents
{
    public function __construct(public string $projectionName) {}
}
