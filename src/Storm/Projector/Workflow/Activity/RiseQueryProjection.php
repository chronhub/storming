<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Promise\IsFirstWorkflowCycle;

final readonly class RiseQueryProjection
{
    public function __invoke(NotificationHub $hub): bool
    {
        $hub->emitWhen(
            $hub->await(IsFirstWorkflowCycle::class),
            fn (NotificationHub $hub) => $hub->emit(EventStreamDiscovered::class)
        );

        return true;
    }
}
