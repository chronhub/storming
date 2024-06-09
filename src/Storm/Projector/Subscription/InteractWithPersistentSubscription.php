<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Closure;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Sprint\IsSprintTerminated;
use Storm\Projector\Workflow\Workflow;

trait InteractWithPersistentSubscription
{
    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->initializeContext($context);

        $this->setupWatcher($context, $keepRunning);

        $this->startProjection();
    }

    public function interact(Closure $callback): mixed
    {
        return value($callback, $this->management->hub());
    }

    private function startProjection(): void
    {
        $activities = ($this->activities)($this->subscriptor, $this->scope);

        $workflow = new Workflow($this->management->hub(), $activities);

        $workflow->process(fn (NotificationHub $hub): bool => $hub->expect(IsSprintTerminated::class));
    }

    private function initializeContext(ContextReader $context): void
    {
        $this->subscriptor->setContext($context, true);

        $this->subscriptor->restoreUserState();
    }

    private function setupWatcher(ContextReader $context, bool $keepRunning): void
    {
        $this->subscriptor->watcher()->subscribe($this->management->hub(), $context);
        $this->subscriptor->watcher()->sprint->runInBackground($keepRunning);
        $this->subscriptor->watcher()->sprint->continue();
    }
}
