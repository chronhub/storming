<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\BatchStreamSleep;
use Storm\Projector\Workflow\Notification\Management\ProjectionLockUpdated;
use Storm\Projector\Workflow\Notification\Management\ProjectionStored;
use Storm\Projector\Workflow\Notification\Promise\HasGap;
use Storm\Projector\Workflow\Notification\Promise\IsBatchStreamBlank;

final readonly class PersistOrUpdate
{
    /**
     * When running blank, we either update the lock after sleeping,
     * or, store the projection
     */
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        if ($hub->await(HasGap::class)) {
            return $next($hub);
        }

        $hub->emitWhen(
            $hub->await(IsBatchStreamBlank::class),
            function (NotificationHub $hub) {
                $hub->emit(BatchStreamSleep::class);

                $hub->emit(new ProjectionLockUpdated());
            },
            fn (NotificationHub $hub) => $hub->emit(new ProjectionStored()),
        );

        return $next($hub);
    }
}
