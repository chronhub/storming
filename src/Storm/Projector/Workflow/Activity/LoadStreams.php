<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Illuminate\Support\Collection;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Iterator\MergeStreamIterator;
use Storm\Projector\Workflow\WorkflowContext;

final readonly class LoadStreams
{
    public function __construct(
        private CollectStreams $collectStreams,
        private SystemClock $clock,
    ) {}

    public function __invoke(WorkflowContext $workflowContext): bool
    {
        $checkpoints = $workflowContext->recognition()->toArray();

        $streams = $this->collectStreams->fromCheckpoints($checkpoints);

        if ($streams instanceof Collection) {
            $streams = new MergeStreamIterator($this->clock, $streams);
        }

        $workflowContext->streamEvent()->set($streams);

        return true;
    }
}
