<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;

final class UserStateRestored
{
    public function __invoke(AgentManager $agentRegistry): void
    {
        $initialState = $agentRegistry->context()->get()->userState();

        $agentRegistry->userState()->init($initialState);
    }
}
