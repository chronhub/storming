<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Management;

use Storm\Projector\Workflow\EmitOnce;

final readonly class ProjectionDiscarded implements EmitOnce
{
    public function __construct(public bool $withEmittedEvents) {}
}
