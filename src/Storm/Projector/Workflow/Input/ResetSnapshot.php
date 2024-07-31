<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Contract\Projector\Component;

final class ResetSnapshot
{
    public function __invoke(Component $component): void
    {
        $component->recognition()->resets();

        $callback = new RestoreUserState();

        $callback($component);
    }
}
