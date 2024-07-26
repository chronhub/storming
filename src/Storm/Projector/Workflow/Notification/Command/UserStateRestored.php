<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentRegistry;

final class UserStateRestored
{
    public function __invoke(AgentRegistry $agentRegistry): void
    {
        $initialState = $agentRegistry->context()->get()->userState();

        $agentRegistry->userState()->init($initialState);
    }
}
