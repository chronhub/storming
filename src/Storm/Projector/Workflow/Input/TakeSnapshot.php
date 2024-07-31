<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Contract\Projector\Component;
use Storm\Projector\Repository\ProjectionSnapshot;

final class TakeSnapshot
{
    public function __invoke(Component $component): ProjectionSnapshot
    {
        return new ProjectionSnapshot(
            $component->recognition()->jsonSerialize(),
            $component->userState()->get()
        );
    }
}
