<?php

declare(strict_types=1);

namespace Storm\Projector\Store\Events;

final readonly class ProjectionDeletedWithEvents
{
    public function __construct(public string $name) {}
}
