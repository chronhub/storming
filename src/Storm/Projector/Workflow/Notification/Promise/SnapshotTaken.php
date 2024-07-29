<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentManager;
use Storm\Projector\Repository\ProjectionSnapshot;

final class SnapshotTaken
{
    public function __invoke(AgentManager $agentRegistry): ProjectionSnapshot
    {
        return new ProjectionSnapshot(
            $agentRegistry->recognition()->jsonSerialize(),
            $agentRegistry->userState()->get()
        );
    }
}
