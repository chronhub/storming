<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentRegistry;
use Storm\Projector\ProjectionStatus;

final readonly class StatusChanged
{
    public function __construct(
        public ProjectionStatus $newStatus,
        public ProjectionStatus $oldStatus,
    ) {}

    public function __invoke(AgentRegistry $agentRegistry): void
    {
        $agentRegistry->status()->set($this->newStatus);
    }
}
