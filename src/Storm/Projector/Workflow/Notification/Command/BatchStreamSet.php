<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Command;

use Storm\Contract\Projector\AgentManager;
use Storm\Projector\Iterator\MergeStreamIterator;

final readonly class BatchStreamSet
{
    public function __construct(public ?MergeStreamIterator $iterator) {}

    public function __invoke(AgentManager $agentRegistry): void
    {
        $agentRegistry->streamEvent()->set($this->iterator);
    }
}
