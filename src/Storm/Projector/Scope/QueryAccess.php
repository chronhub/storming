<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\QueryProjectorScope;
use Storm\Projector\Workflow\WorkflowContext;

final readonly class QueryAccess implements QueryProjectorScope
{
    public function __construct(
        private WorkflowContext $workflowContext,
        private SystemClock $clock
    ) {}

    public function stop(): void
    {
        $this->workflowContext->sprint()->halt();
    }

    public function streamName(): string
    {
        return $this->workflowContext->processedStream()->get();
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
