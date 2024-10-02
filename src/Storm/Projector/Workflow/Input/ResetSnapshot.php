<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Projector\Factory\Component\ComponentManager;

final class ResetSnapshot
{
    public function __invoke(ComponentManager $component): void
    {
        $component->recognition()->resets();

        $callback = new RestoreUserState;

        $callback($component);
    }
}
