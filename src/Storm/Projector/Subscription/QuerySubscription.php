<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Closure;
use Storm\Contract\Projector\AgentRegistry;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\QuerySubscriber;
use Storm\Projector\Factory\WorkflowBuilder;
use Storm\Projector\Workflow\Notification\Command\UserStateRestored;

final readonly class QuerySubscription implements QuerySubscriber
{
    public function __construct(
        protected AgentRegistry $registry,
        protected WorkflowBuilder $workflowBuilder,
        protected NotificationHub $hub,
    ) {}

    public function resets(): void
    {
        $this->registry->recognition()->resets();

        $this->restoreUserState();
    }

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

    /**
     * Initialize context when the projection is started.
     * If keep state is enabled, user state will be kept.
     */
    protected function initializeContext(ContextReader $context): void
    {
        if (! $this->registry->context()->isset()) {
            $this->registry->context()->set($context);

            $this->restoreUserState();
        } elseif ($this->registry->context()->get()->keepState() === false) {
            $this->restoreUserState();
        }
    }

    /**
     * Restore user state to his original state.
     */
    protected function restoreUserState(): void
    {
        $this->hub->emit(UserStateRestored::class);
    }
}
