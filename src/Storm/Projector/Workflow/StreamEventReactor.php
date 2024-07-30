<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Closure;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\Header;
use Storm\Contract\Projector\ProjectorScope;
use Storm\Projector\Checkpoint\StreamPoint;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;
use Storm\Projector\Workflow\Notification\Management\PerformWhenThresholdIsReached;
use Storm\Stream\StreamPosition;

use function pcntl_signal_dispatch;

readonly class StreamEventReactor
{
    public function __construct(
        protected Closure $reactors,
        protected ProjectorScope $projector,
        protected bool $dispatchSignal
    ) {}

    public function __invoke(WorkflowContext $workflowContext, string $streamName, DomainEvent $event, StreamPosition $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested();

        $streamPoint = new StreamPoint($streamName, $expectedPosition, $event->header(Header::EVENT_TIME));

        if ($workflowContext->processStreamEvent($streamPoint)->isGap()) {
            return false;
        }

        return $this->handleEvent($workflowContext, $event);
    }

    protected function handleEvent(WorkflowContext $workflowContext, DomainEvent $event): bool
    {
        $workflowContext->incrementBatchStream();

        $this->reactOn($workflowContext, $event);

        $workflowContext->emit(new PerformWhenThresholdIsReached());

        return $workflowContext->sprint()->inProgress();
    }

    protected function reactOn(WorkflowContext $workflowContext, DomainEvent $event): void
    {
        $userState = $this->getUserState($workflowContext);
        $eventScope = new EventScope($event, $this->projector, $userState);

        ($this->reactors)($eventScope);

        $this->updateUserStateIfInitialized($workflowContext, $userState);

        if ($eventScope->isAcked()) {
            $workflowContext->stat()->acked()->merge($event::class);
        }
    }

    protected function getUserState(WorkflowContext $workflowContext): ?UserStateScope
    {
        if (! $workflowContext->isUserStateInitialized()) {
            return null;
        }

        $userState = $workflowContext->userState()->get();

        return new UserStateScope($userState);
    }

    protected function updateUserStateIfInitialized(WorkflowContext $workflowContext, ?UserStateScope $scope): void
    {
        if ($scope !== null) {
            $workflowContext->userState()->put($scope->state());
        }
    }

    protected function dispatchSignalIfRequested(): void
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }
    }
}
