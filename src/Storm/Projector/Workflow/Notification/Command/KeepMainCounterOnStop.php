<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentRegistry;

// fixme interfere with keep state query
// fixme re run persistent projection not aware of this
final readonly class KeepMainCounterOnStop
{
    public function __construct(public bool $keepOnStop = true) {}

    public function __invoke(AgentRegistry $agentRegistry): void
    {
        $agentRegistry->stat()->main()->doNotReset($this->keepOnStop);
    }
}
