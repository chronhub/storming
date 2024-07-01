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
use Storm\Projector\Workflow\Notification\Batch\BatchIncremented;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointInserted;
use Storm\Projector\Workflow\Notification\Management\ProjectionPersistedWhenThresholdIsReached;
use Storm\Projector\Workflow\Notification\Sprint\IsSprintRunning;
use Storm\Projector\Workflow\Notification\Stream\StreamEventAcked;
use Storm\Projector\Workflow\Notification\UserState\CurrentUserState;
use Storm\Projector\Workflow\Notification\UserState\IsUserStateInitialized;
use Storm\Projector\Workflow\Notification\UserState\UserStateChanged;

use function pcntl_signal_dispatch;

readonly class StreamEventReactor
{
    public function __construct(
        protected Closure $reactors,
        protected ProjectorScope $projector,
        protected bool $dispatchSignal
    ) {}

    /**
     * @param positive-int $expectedPosition
     */
    public function __invoke(NotificationHub $hub, string $streamName, DomainEvent $event, int $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested();

        $notification = new CheckpointInserted($streamName, $expectedPosition, $event->header(Header::EVENT_TIME));

        if ($this->hasGap($hub, $notification)) {
            return false;
        }

        return $this->handleEvent($hub, $event);
    }

    protected function handleEvent(NotificationHub $hub, DomainEvent $event): bool
    {
        $hub->notify(BatchIncremented::class);

        $this->reactOn($hub, $event);

        $hub->trigger(new ProjectionPersistedWhenThresholdIsReached());

        return $hub->expect(IsSprintRunning::class);
    }

    protected function reactOn(NotificationHub $hub, DomainEvent $event): void
    {
        $userState = $this->getUserState($hub);
        $eventScope = new EventScope($event, $this->projector, $userState);

        ($this->reactors)($eventScope);

        $this->updateUserStateIfInitialized($hub, $userState);

        $hub->notifyWhen(
            $eventScope->isAcked(),
            fn (NotificationHub $hub) => $hub->notify(StreamEventAcked::class, $event::class)
        );
    }

    protected function hasGap(NotificationHub $hub, CheckpointInserted $notification): bool
    {
        /** @var Checkpoint $checkpoint */
        $checkpoint = $hub->expect($notification);

        return $checkpoint->isGap();
    }

    protected function getUserState(NotificationHub $hub): ?UserStateScope
    {
        if (! $hub->expect(IsUserStateInitialized::class)) {
            return null;
        }

        return new UserStateScope($hub->expect(CurrentUserState::class));
    }

    protected function updateUserStateIfInitialized(NotificationHub $hub, ?UserStateScope $userState): void
    {
        $hub->notifyWhen(
            $userState !== null,
            fn (NotificationHub $hub) => $hub->notify(UserStateChanged::class, $userState->state())
        );
    }

    protected function dispatchSignalIfRequested(): void
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }
    }
}
