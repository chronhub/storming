<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Projector\Workflow\ComponentRegistry;

final class RestoreUserState
{
    public function __invoke(ComponentRegistry $component): void
    {
        $initialState = $component->context()->get()->userState();

        $component->userState()->init($initialState);
    }
}
