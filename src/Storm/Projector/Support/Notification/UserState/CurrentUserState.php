<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\UserState;

use Storm\Contract\Projector\Subscriptor;

final class CurrentUserState
{
    public function __invoke(Subscriptor $subscriptor): array
    {
        return $subscriptor->watcher()->userState()->get();
    }
}
