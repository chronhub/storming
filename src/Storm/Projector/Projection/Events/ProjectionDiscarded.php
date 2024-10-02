<?php

declare(strict_types=1);

namespace Storm\Projector\Projection\Events;

use Storm\Projector\Workflow\NotifyOnce;

final readonly class ProjectionDiscarded implements NotifyOnce
{
    public function __construct(public bool $withEmittedEvents) {}
}
