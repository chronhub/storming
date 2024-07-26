<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\IsFirstWorkflowCycle;

final readonly class RiseQueryProjection
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        $hub->emitWhen(
            $hub->await(IsFirstWorkflowCycle::class),
            fn (NotificationHub $hub) => $hub->emit(EventStreamDiscovered::class)
        );

        return $next($hub);
    }
}
