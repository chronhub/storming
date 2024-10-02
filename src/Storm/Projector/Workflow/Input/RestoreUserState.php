<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Projector\Factory\Component\ComponentManager;

final class RestoreUserState
{
    public function __invoke(ComponentManager $component): void
    {
        $initialState = $component->context()->get()->userState();

        $component->userState()->init($initialState);
    }
}
