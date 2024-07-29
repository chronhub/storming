<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;

final readonly class StreamProcessed
{
    public function __construct(public string $streamName) {}

    public function __invoke(AgentManager $agentRegistry): void
    {
        $agentRegistry->processedStream()->set($this->streamName);
    }
}
