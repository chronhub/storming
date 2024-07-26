<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\BatchStreamReset;
use Storm\Projector\Workflow\Notification\Command\MainCounterReset;
use Storm\Projector\Workflow\Notification\Command\NewEventStreamReset;
use Storm\Projector\Workflow\Notification\Command\StreamEventAckedReset;
use Storm\Projector\Workflow\Notification\Command\TimeReset;
use Storm\Projector\Workflow\Notification\Command\WorkflowCycleReset;
use Storm\Projector\Workflow\Notification\ForgetOnCycleRenewed;
use Storm\Projector\Workflow\Notification\ForgetOnTermination;
use Storm\Projector\Workflow\Notification\Promise\StreamEventProcessed;
use Storm\Projector\Workflow\Notification\RecoverableGapDetected;
use Storm\Projector\Workflow\Notification\ResetOnCycleRenewed;
use Storm\Projector\Workflow\Notification\ResetOnTermination;
use Storm\Projector\Workflow\Notification\UnrecoverableGapDetected;

class RotationEventMap
{
    /**
     * Listeners to reset on cycle renewed and termination.
     *
     * @var array|array<string, array<class-string>>
     */
    protected array $resetsListeners = [
        ResetOnCycleRenewed::class => [
            BatchStreamReset::class,
            NewEventStreamReset::class,
        ],

        ResetOnTermination::class => [
            WorkflowCycleReset::class,
            TimeReset::class,
            MainCounterReset::class,
            StreamEventAckedReset::class,
        ],
    ];

    /**
     * Listeners to forget on cycle renewed and termination.
     *
     * @var array|array<string, array<class-string>>
     */
    protected array $forgetsListeners = [
        ForgetOnCycleRenewed::class => [
            StreamEventProcessed::class,
            RecoverableGapDetected::class,
            UnrecoverableGapDetected::class,
        ],

        ForgetOnTermination::class => [],
    ];

    /**
     * Handle rotation events.
     *
     * @param  class-string $event
     * @return bool         whether the event has been handled
     */
    public function handle(string $event, NotificationHub $hub): bool
    {
        if (isset($this->resetsListeners[$event])) {
            $hub->emitMany(...$this->resetsListeners[$event]);

            return true;
        }

        if (isset($this->forgetsListeners[$event])) {
            foreach ($this->forgetsListeners[$event] as $listener) {
                $hub->forgetEvent($listener);
            }

            return true;
        }

        return false;
    }
}
