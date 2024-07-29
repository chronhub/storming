<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Closure;
use Storm\Contract\Projector\AgentRegistry;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ReadModelSubscriber;
use Storm\Projector\Factory\WorkflowBuilder;
use Storm\Projector\Workflow\Notification\Command\UserStateRestored;

final readonly class ReadModelSubscription implements ReadModelSubscriber
{
    public function __construct(
        protected AgentRegistry $registry,
        protected WorkflowBuilder $workflowBuilder,
        protected NotificationHub $hub,
    ) {}

    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->initializeContext($context);

        $this->registry->subscribe($this->hub, $context);
        $this->registry->sprint()->runInBackground($keepRunning);
        $this->registry->sprint()->continue();

        $workflow = $this->workflowBuilder->create($this->registry);
        $workflow->process();
    }

    public function interact(Closure $callback): mixed
    {
        return value($callback, $this->hub);
    }

    protected function initializeContext(ContextReader $context): void
    {
        $this->registry->context()->set($context);

        $this->hub->emit(UserStateRestored::class);
    }
}
