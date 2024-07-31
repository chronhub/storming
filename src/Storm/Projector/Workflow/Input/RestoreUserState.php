<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Contract\Projector\Component;

final class RestoreUserState
{
    public function __invoke(Component $component): void
    {
        $initialState = $component->context()->get()->userState();

        $component->userState()->init($initialState);
    }
}
