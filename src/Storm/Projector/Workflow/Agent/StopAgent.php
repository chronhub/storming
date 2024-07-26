<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Agent;

use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ShouldAgentSubscribe;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;
use Storm\Projector\Workflow\Notification\Command\SprintTerminated;
use Storm\Projector\Workflow\Notification\Promise\IsSprintTerminated;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;

class StopAgent implements ShouldAgentSubscribe
{
    public function subscribe(NotificationHub $hub, ContextReader $context): void
    {
        $callbacks = $context->haltOnCallback();

        if ($callbacks === []) {
            return;
        }

        $callback = $callbacks[0];

        $this->stopWhen($hub, $callback);

        $hub->addEvent(SprintTerminated::class, function (NotificationHub $hub): void {
            $hub->forgetEvent(ShouldTerminateWorkflow::class);
        });
    }

    /**
     * Stop the projector when the given callback returns true.
     *
     * @param callable(NotificationHub): bool $callback
     */
    protected function stopWhen(NotificationHub $hub, callable $callback): string
    {
        $listener = ShouldTerminateWorkflow::class;

        $hub->addEvent($listener, function (NotificationHub $hub) use ($callback): void {
            // prevents stopping the projector when the projection is already terminated
            $isTerminated = $hub->await(IsSprintTerminated::class);

            if (! $isTerminated && $callback($hub) === true) {
                $hub->emit(SprintStopped::class);
            }
        });

        return $listener;
    }
}
