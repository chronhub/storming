<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Watcher;

use Closure;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Workflow\Notification\Batch\BatchIncremented;
use Storm\Projector\Workflow\Notification\Checkpoint\GapDetected;
use Storm\Projector\Workflow\Notification\Checkpoint\RecoverableGapDetected;
use Storm\Projector\Workflow\Notification\Checkpoint\UnrecoverableGapDetected;
use Storm\Projector\Workflow\Notification\Cycle\CurrentCycle;
use Storm\Projector\Workflow\Notification\Cycle\CycleIncremented;
use Storm\Projector\Workflow\Notification\Cycle\CycleRenewed;
use Storm\Projector\Workflow\Notification\MasterCounter\CurrentMasterCount;
use Storm\Projector\Workflow\Notification\MasterCounter\KeepMasterCounterOnStop;
use Storm\Projector\Workflow\Notification\Sprint\SprintStopped;
use Storm\Projector\Workflow\Notification\Sprint\SprintTerminated;
use Storm\Projector\Workflow\Notification\Stream\NoEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Timer\CurrentTime;
use Storm\Projector\Workflow\Notification\Timer\GetElapsedTime;

use function method_exists;
use function pcntl_signal;
use function pcntl_signal_dispatch;
use function ucfirst;

class StopWatcher
{
    public const string REQUESTED = 'requested';

    public const string EMPTY_EVENT_STREAM = 'emptyEventStream';

    public const string GAP_DETECTED = 'gapDetected';

    public const string CYCLE_REACHED = 'cycleReached';

    public const string COUNTER_REACHED = 'counterReached';

    public const string TIME_EXPIRED = 'timeExpired';

    public const string SIGNAL_RECEIVED = 'signalReceived';

    /**
     * @var array<class-string>
     */
    protected array $events = [];

    public function subscribe(NotificationHub $hub, ContextReader $context): void
    {
        $callbacks = $context->haltOnCallback();

        foreach ($callbacks as $name => $callback) {
            $method = 'stopWhen'.ucfirst($name);

            /**
             * @covers stopWhenRequested
             * @covers stopWhenEmptyEventStream
             * @covers stopWhenGapDetected
             * @covers stopWhenCounterReached
             * @covers stopWhenCycleReached
             * @covers stopWhenTimeExpired
             * @covers stopWhenSignalReceived
             */
            if (! method_exists($this, $method)) {
                throw new InvalidArgumentException("Invalid stop watcher callback $name");
            }

            $value = value($callback);
            $this->events[] = $this->{$method}($hub, $value);
        }

        $hub->addListener(SprintTerminated::class, function (NotificationHub $hub): void {
            foreach ($this->events as $event) {
                $hub->forgetListener($event);
            }

            $this->events = [];
        });
    }

    protected function stopWhenRequested(NotificationHub $hub, bool &$shouldStop): string
    {
        $listener = CycleRenewed::class;

        $hub->addListener($listener, function (NotificationHub $hub) use (&$shouldStop): void {
            if ($shouldStop) {
                $this->notifySprintStopped($hub);
            }
        });

        return $listener;
    }

    protected function stopWhenSignalReceived(NotificationHub $hub, array $signals): string
    {
        $listener = CycleRenewed::class;

        pcntl_signal_dispatch();

        $hub->addListener($listener, function (NotificationHub $hub) use ($signals): void {
            foreach ($signals as $signal) {
                pcntl_signal($signal, function () use ($hub): void {
                    $this->notifySprintStopped($hub);
                });
            }
        });

        return $listener;
    }

    protected function stopWhenEmptyEventStream(NotificationHub $hub, ?int $expiredAt): string
    {
        $listener = NoEventStreamDiscovered::class;

        $callback = $expiredAt === null
            ? fn (NotificationHub $hub) => $this->notifySprintStopped($hub)
            : $this->expirationCallback($expiredAt);

        $hub->addListener($listener, $callback);

        return $listener;
    }

    protected function stopWhenGapDetected(NotificationHub $hub, GapType $gapType): string
    {
        $listener = match ($gapType) {
            GapType::RECOVERABLE_GAP => RecoverableGapDetected::class,
            GapType::UNRECOVERABLE_GAP => UnrecoverableGapDetected::class,
            GapType::IN_GAP => GapDetected::class,
        };

        $hub->addListener($listener, fn (NotificationHub $hub) => $this->notifySprintStopped($hub));

        return $listener;
    }

    protected function stopWhenCounterReached(NotificationHub $hub, array $values): string
    {
        [$limit, $resetOnStop] = $values;
        $listener = BatchIncremented::class;

        $hub->notify(KeepMasterCounterOnStop::class, ! $resetOnStop);

        $hub->addListener($listener, function (NotificationHub $hub) use ($limit): void {
            $currentCount = $hub->expect(CurrentMasterCount::class);

            if ($limit <= $currentCount) {
                $this->notifySprintStopped($hub);
            }
        });

        return $listener;
    }

    protected function stopWhenCycleReached(NotificationHub $hub, int $expectedCycle): string
    {
        $listener = CycleIncremented::class;

        $hub->addListener($listener, function (NotificationHub $hub) use ($expectedCycle): void {
            $currentCycle = $hub->expect(CurrentCycle::class);

            if ($currentCycle === $expectedCycle) {
                $this->notifySprintStopped($hub);
            }
        });

        return $listener;
    }

    protected function stopWhenTimeExpired(NotificationHub $hub, int $expiredAt): string
    {
        $listener = CycleRenewed::class;

        $hub->addListener($listener, $this->expirationCallback($expiredAt));

        return $listener;
    }

    protected function expirationCallback(int $expiredAt): Closure
    {
        return function (NotificationHub $hub) use ($expiredAt): void {
            $currentTime = (int) $hub->expect(CurrentTime::class);
            $elapsedTime = (int) $hub->expect(GetElapsedTime::class);

            if ($expiredAt < $currentTime + $elapsedTime) {
                $this->notifySprintStopped($hub);
            }
        };
    }

    protected function notifySprintStopped(NotificationHub $hub): void
    {
        $hub->notify(SprintStopped::class);
    }
}
