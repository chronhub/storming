<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Input;

use Storm\Contract\Projector\Component;

final class IsUserStateInitialized
{
    public function __invoke(Component $component): bool
    {
        return $component->context()->get()->isUserStateInitialized();
    }
}
