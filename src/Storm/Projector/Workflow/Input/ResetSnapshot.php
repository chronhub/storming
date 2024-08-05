<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Projector\Workflow\ComponentRegistry;

final class ResetSnapshot
{
    public function __invoke(ComponentRegistry $component): void
    {
        $component->recognition()->resets();

        $callback = new RestoreUserState();

        $callback($component);
    }
}
