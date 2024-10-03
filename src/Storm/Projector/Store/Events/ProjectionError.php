<?php

declare(strict_types=1);

namespace Storm\Projector\Store\Events;

use Throwable;

final readonly class ProjectionError
{
    public function __construct(
        public string $projectionName,
        public string $event,
        public Throwable $error
    ) {}
}
