<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentRegistry;
use Storm\Projector\Iterator\MergeStreamIterator;

final class PullBatchStream
{
    public function __invoke(AgentRegistry $agentRegistry): ?MergeStreamIterator
    {
        return $agentRegistry->streamEvent()->pull();
    }
}
