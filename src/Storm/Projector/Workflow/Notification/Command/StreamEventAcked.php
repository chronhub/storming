<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;

final readonly class StreamEventAcked
{
    public function __construct(public string $event) {}

    public function __invoke(AgentManager $agentRegistry): void
    {
        $agentRegistry->stat()->acked()->merge($this->event);
    }
}
