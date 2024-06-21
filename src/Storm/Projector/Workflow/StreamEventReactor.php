<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Closure;
use DateTimeImmutable;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\Header;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectorScope;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Workflow\Notification\Batch\BatchIncremented;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointInserted;
use Storm\Projector\Workflow\Notification\Management\ProjectionPersistedWhenThresholdIsReached;
use Storm\Projector\Workflow\Notification\Sprint\IsSprintRunning;
use Storm\Projector\Workflow\Notification\Stream\StreamEventAcked;
use Storm\Projector\Workflow\Notification\UserState\CurrentUserState;
use Storm\Projector\Workflow\Notification\UserState\IsUserStateInitialized;
use Storm\Projector\Workflow\Notification\UserState\UserStateChanged;

use function is_array;
use function pcntl_signal_dispatch;

readonly class StreamEventReactor
{
    public function __construct(
        protected Closure $reactors,
        protected ProjectorScope $scope,
        protected bool $dispatchSignal
    ) {
    }

    /**
     * @param positive-int $expectedPosition
     */
    public function __invoke(NotificationHub $hub, string $streamName, DomainEvent $event, int $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested();

        if (! $this->hasNoGap($hub, $streamName, $expectedPosition, $event->header(Header::EVENT_TIME))) {
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
        $initializedState = $this->getUserState($hub);

        $resetScope = ($this->scope)($event, $initializedState);

        ($this->reactors)($this->scope);

        // Update user state if it was initialized,
        // no matter if the event was acked or not
        $this->updateUserState($hub, $initializedState, $this->scope->getState());

        $hub->notifyWhen(
            $this->scope->isAcked(),
            fn (NotificationHub $hub) => $hub->notify(StreamEventAcked::class, $event::class)
        );

        $resetScope();
    }

    protected function hasNoGap(NotificationHub $hub, string $streamName, int $expectedPosition, string|DateTimeImmutable $eventTime): bool
    {
        /** @var Checkpoint $checkpoint */
        $checkpoint = $hub->expect(new CheckpointInserted($streamName, $expectedPosition, $eventTime));

        return $checkpoint->isGap();
    }

    protected function getUserState(NotificationHub $hub): ?array
    {
        return $hub->expect(IsUserStateInitialized::class)
            ? $hub->expect(CurrentUserState::class) : null;
    }

    protected function updateUserState(NotificationHub $hub, ?array $initializedState, ?array $userState): void
    {
        if (is_array($initializedState) && is_array($userState)) {
            $hub->notify(UserStateChanged::class, $userState);
        }
    }

    protected function dispatchSignalIfRequested(): void
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }
    }
}
