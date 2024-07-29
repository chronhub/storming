<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;
use Storm\Projector\ProjectionStatus;

final readonly class StatusDisclosed
{
    public function __construct(
        public ProjectionStatus $newStatus,
        public ProjectionStatus $oldStatus
    ) {}

    public function __invoke(AgentManager $agentRegistry): void
    {
        $agentRegistry->status()->set($this->newStatus);
    }
}
