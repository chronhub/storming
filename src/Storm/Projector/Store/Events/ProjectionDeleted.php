<?php

declare(strict_types=1);

namespace Storm\Projector\Store\Events;

final readonly class ProjectionDeleted
{
    public function __construct(public string $name) {}
}
