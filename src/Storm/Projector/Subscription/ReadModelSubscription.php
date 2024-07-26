<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Closure;
use Storm\Contract\Projector\AgentRegistry;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\ReadModelManagement;
use Storm\Contract\Projector\ReadModelSubscriber;
use Storm\Projector\Workflow\Notification\Command\UserStateRestored;

final readonly class ReadModelSubscription implements ReadModelSubscriber
{
    public function __construct(
        protected AgentRegistry $registry,
        protected ReadModelManagement $management,
    ) {}

    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->initializeContext($context);

        $this->registry->subscribe($this->management->hub(), $context);
        $this->registry->sprint()->runInBackground($keepRunning);
        $this->registry->sprint()->continue();

        $workflow = $this->registry->newWorkflow();
        $workflow->process();
    }

    public function interact(Closure $callback): mixed
    {
        return value($callback, $this->management->hub());
    }

    protected function initializeContext(ContextReader $context): void
    {
        $this->registry->context()->set($context);

        $this->management->hub()->emit(UserStateRestored::class);
    }
}
