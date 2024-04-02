<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\UserState;

use Storm\Contract\Projector\Subscriptor;

final class UserStateRestored
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->restoreUserState();
    }
}
