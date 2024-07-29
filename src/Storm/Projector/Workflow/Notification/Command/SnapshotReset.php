<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;

final class SnapshotReset
{
    public function __invoke(AgentManager $agents): void
    {
        $agents->recognition()->resets();

        // checkMe
        $restoreUserState = new UserStateRestored();
        $restoreUserState($agents);
    }
}
