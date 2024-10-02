<?php

declare(strict_types=1);

namespace Storm\Projector\Stream;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\Header;
use Storm\Projector\Checkpoint\StreamPoint;
use Storm\Projector\Provider\Events\PerformWhenThresholdIsReached;
use Storm\Projector\Scope\ProjectorScopeFactory;
use Storm\Projector\Workflow\Process;
use Storm\Stream\StreamPosition;

use function pcntl_signal_dispatch;

readonly class StreamEventReactor
{
    public function __construct(
        protected ProjectorScopeFactory $projectorScope
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

        $process->dispatch(new PerformWhenThresholdIsReached);

        return $process->sprint()->inProgress();
    }

    protected function reactOn(Process $process, DomainEvent $event): void
    {
        $userState = $this->getUserState($process);

        $projector = $this->projectorScope->handle($event, $userState);

        if ($projector->userState() !== null) {
            $process->userState()->put($projector->userState()->all());
        }

        if ($projector->event() instanceof DomainEvent) {
            $process->metrics()->increment('acked');
        }
    }

    protected function getUserState(Process $process): ?array
    {
        if (! $process->context()->get()->isUserStateInitialized()) {
            return null;
        }

        return $process->userState()->get();
    }

    protected function dispatchSignalIfRequested(bool $dispatchSignal): void
    {
        if ($dispatchSignal) {
            pcntl_signal_dispatch();
        }
    }
}
