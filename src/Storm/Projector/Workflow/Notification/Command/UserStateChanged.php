<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;

final readonly class UserStateChanged
{
    public function __construct(public array $userState) {}

    public function __invoke(AgentManager $agentRegistry): void
    {
        $agentRegistry->userState()->put($this->userState);
    }
}
