<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentRegistry;

final readonly class CheckpointUpdated
{
    public function __construct(public array $checkpoints) {}

    public function __invoke(AgentRegistry $agentRegistry): void
    {
        $agentRegistry->recognition()->update($this->checkpoints);
    }
}
