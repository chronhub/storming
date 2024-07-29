<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Closure;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ReadModelSubscriber;
use Storm\Projector\Factory\WorkflowBuilder;

final readonly class ReadModelSubscription implements ReadModelSubscriber
{
    public function __construct(
        protected WorkflowBuilder $workflowBuilder,
        protected NotificationHub $hub,
    ) {}

    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->workflowBuilder
            ->newWorkflow($context, $keepRunning)
            ->process();
    }

    public function interact(Closure $callback): mixed
    {
        return value($callback, $this->hub);
    }
}
