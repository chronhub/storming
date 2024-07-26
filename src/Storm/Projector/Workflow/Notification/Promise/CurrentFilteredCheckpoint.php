<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Contract\Projector\AgentRegistry;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Projector\Checkpoint\Checkpoint;

/**
 * @phpstan-import-type CheckpointArray from Checkpoint
 */
final class CurrentFilteredCheckpoint
{
    /**
     * Returns the current checkpoint as an array.
     * It also may have been filtered from his gaps and gap type
     * if projection option recordGaps is disabled.
     *
     * @see ProjectionOption::recordGaps()
     *
     * @return array<CheckpointArray>
     */
    public function __invoke(AgentRegistry $agentRegistry): array
    {
        return $agentRegistry->recognition()->jsonSerialize();
    }
}
