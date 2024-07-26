<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Closure;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\Header;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectorScope;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;
use Storm\Projector\Workflow\Notification\Command\BatchStreamIncrements;
use Storm\Projector\Workflow\Notification\Command\StreamEventAcked;
use Storm\Projector\Workflow\Notification\Command\UserStateChanged;
use Storm\Projector\Workflow\Notification\Management\ProjectionPersistedWhenThresholdIsReached;
use Storm\Projector\Workflow\Notification\Promise\CurrentUserState;
use Storm\Projector\Workflow\Notification\Promise\IsSprintRunning;
use Storm\Projector\Workflow\Notification\Promise\IsUserStateInitialized;
use Storm\Projector\Workflow\Notification\Promise\StreamEventProcessed;
use Storm\Stream\StreamPosition;

use function pcntl_signal_dispatch;

readonly class StreamEventReactor
{
    public function __construct(
        protected Closure $reactors,
        protected ProjectorScope $projector,
        protected bool $dispatchSignal
    ) {}

    public function __invoke(NotificationHub $hub, string $streamName, DomainEvent $event, StreamPosition $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested();

        $notification = new StreamEventProcessed($streamName, $expectedPosition, $event->header(Header::EVENT_TIME));

        if ($this->hasGap($hub, $notification)) {
            return false;
        }

        return $this->handleEvent($hub, $event);
    }

    protected function handleEvent(NotificationHub $hub, DomainEvent $event): bool
    {
        $hub->emit(BatchStreamIncrements::class);

        $this->reactOn($hub, $event);

        $hub->emit(new ProjectionPersistedWhenThresholdIsReached());

        return $hub->await(IsSprintRunning::class);
    }

    protected function reactOn(NotificationHub $hub, DomainEvent $event): void
    {
        $userState = $this->getUserState($hub);
        $eventScope = new EventScope($event, $this->projector, $userState);

        ($this->reactors)($eventScope);

        $this->updateUserStateIfInitialized($hub, $userState);

        $hub->emitWhen(
            $eventScope->isAcked(),
            fn (NotificationHub $hub) => $hub->emit(StreamEventAcked::class, $event::class)
        );
    }

    protected function hasGap(NotificationHub $hub, StreamEventProcessed $notification): bool
    {
        /** @var Checkpoint $checkpoint */
        $checkpoint = $hub->await($notification);

        return $checkpoint->isGap();
    }

    protected function getUserState(NotificationHub $hub): ?UserStateScope
    {
        if (! $hub->await(IsUserStateInitialized::class)) {
            return null;
        }

        return new UserStateScope($hub->await(CurrentUserState::class));
    }

    protected function updateUserStateIfInitialized(NotificationHub $hub, ?UserStateScope $scope): void
    {
        $hub->emitWhen(
            $scope !== null,
            fn (NotificationHub $hub) => $hub->emit(UserStateChanged::class, $scope->state())
        );
    }

    protected function dispatchSignalIfRequested(): void
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }
    }
}
