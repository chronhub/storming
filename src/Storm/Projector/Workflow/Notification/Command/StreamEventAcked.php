<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentRegistry;

final readonly class StreamEventAcked
{
    public function __construct(public string $event) {}

    public function __invoke(AgentRegistry $agentRegistry): void
    {
        $agentRegistry->stat()->acked()->merge($this->event);
    }
}
