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
use Storm\Projector\Workflow\Input\IsUserStateInitialized;
use Storm\Projector\Workflow\Management\PerformWhenThresholdIsReached;
use Storm\Stream\StreamPosition;

use function pcntl_signal_dispatch;

readonly class StreamEventReactor
{
    public function __construct(
        protected Closure $reactors,
        protected ProjectorScope $projector
    ) {}

    public function __invoke(Process $process, string $streamName, DomainEvent $event, StreamPosition $expectedPosition): bool
    {
        $this->dispatchSignalIfRequested($process->option()->getSignal());

        $streamPoint = new StreamPoint($streamName, $expectedPosition, $event->header(Header::EVENT_TIME));

        if ($process->recognition()->record($streamPoint)->isGap()) {
            return false;
        }

        return $this->handleEvent($process, $event);
    }

    protected function handleEvent(Process $process, DomainEvent $event): bool
    {
        $process->metrics()->incrementBatchStream();

        $this->reactOn($process, $event);

        $process->dispatch(new PerformWhenThresholdIsReached());

        return $process->sprint()->inProgress();
    }

    protected function reactOn(Process $process, DomainEvent $event): void
    {
        $userState = $this->getUserState($process);
        $eventScope = new EventScope($event, $this->projector, $userState);

        ($this->reactors)($eventScope);

        $this->updateUserStateIfInitialized($process, $userState);

        if ($eventScope->isAcked()) {
            $process->metrics()->acked++;
        }
    }

    protected function getUserState(Process $process): ?UserStateScope
    {
        if (! $process->call(new IsUserStateInitialized())) {
            return null;
        }

        $userState = $process->userState()->get();

        return new UserStateScope($userState);
    }

    protected function updateUserStateIfInitialized(Process $process, ?UserStateScope $scope): void
    {
        if ($scope !== null) {
            $process->userState()->put($scope->state());
        }
    }

    protected function dispatchSignalIfRequested(bool $dispatchSignal): void
    {
        if ($dispatchSignal) {
            pcntl_signal_dispatch();
        }
    }
}
