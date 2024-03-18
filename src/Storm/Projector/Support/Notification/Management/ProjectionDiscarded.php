<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Management;

final readonly class ProjectionDiscarded
{
    public function __construct(public bool $withEmittedEvents)
    {
    }
}
