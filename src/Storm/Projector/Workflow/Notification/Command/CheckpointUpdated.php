<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;

final readonly class CheckpointUpdated
{
    public function __construct(public array $checkpoints) {}

    public function __invoke(AgentManager $agentRegistry): void
    {
        $agentRegistry->recognition()->update($this->checkpoints);
    }
}
