<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Projector\Storage\ProjectionSnapshot;
use Storm\Projector\Workflow\ComponentRegistry;

final class TakeSnapshot
{
    public function __invoke(ComponentRegistry $component): ProjectionSnapshot
    {
        return new ProjectionSnapshot(
            $component->recognition()->jsonSerialize(),
            $component->userState()->get()
        );
    }
}
