<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Contract\Projector\Component;

final class IsSprintTerminated
{
    public function __invoke(Component $component): bool
    {
        return ! $component->sprint()->inBackground()
            || ! $component->sprint()->inProgress();
    }
}
