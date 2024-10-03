<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Projector\Factory\Component\ComponentManager;
use Storm\Projector\Store\ProjectionSnapshot;

final class TakeSnapshot
{
    public function __invoke(ComponentManager $component): ProjectionSnapshot
    {
        return new ProjectionSnapshot(
            $component->recognition()->jsonSerialize(),
            $component->userState()->get()
        );
    }
}
