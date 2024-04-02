<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\UserState;

use Storm\Contract\Projector\Subscriptor;

final class IsUserStateInitialized
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->isUserStateInitialized();
    }
}
