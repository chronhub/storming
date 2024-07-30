<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Message\DomainEvent;
use Storm\Projector\Iterator\MergeStreamIterator;
use Storm\Projector\Workflow\WorkflowContext;
use Storm\Stream\StreamPosition;

use function gc_collect_cycles;

final class HandleStreamEvent
{
    /** @var callable{WorkflowContext, string, DomainEvent, StreamPosition} */
    private $eventProcessor;

    public function __construct(callable $eventProcessor)
    {
        $this->eventProcessor = $eventProcessor;
    }

    public function __invoke(WorkflowContext $workflowContext): bool
    {
        $streams = $workflowContext->streamEvent()->pull();

        if (! $streams instanceof MergeStreamIterator) {
            return true;
        }

        while ($streams->valid()) {
            $workflowContext->processedStream()->set($streams->streamName());

            $continue = ($this->eventProcessor)($workflowContext, $streams->streamName(), $streams->current(), $streams->key());

            if (! $continue || ! $workflowContext->sprint()->inProgress()) {
                break;
            }

            $streams->next();
        }

        gc_collect_cycles();

        return true;
    }
}
