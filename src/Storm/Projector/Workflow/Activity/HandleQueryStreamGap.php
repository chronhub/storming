<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\SleepOnGap;
use Storm\Projector\Workflow\Notification\Promise\CurrentGapType;
use Storm\Projector\Workflow\Notification\Promise\HasGap;

final class HandleQueryStreamGap
{
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        $hub->emitWhen(
            $hub->await(HasGap::class),
            function (NotificationHub $hub): void {
                $hub->addEvent($hub->await(CurrentGapType::class)->value, fn () => null);

                $hub->emit(SleepOnGap::class);
            });

        return $next($hub);
    }
}
