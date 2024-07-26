<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Subscription\InteractWithManagement;
use Storm\Projector\Workflow\Notification\Command\SleepOnGap;
use Storm\Projector\Workflow\Notification\Management\ProjectionStored;
use Storm\Projector\Workflow\Notification\Promise\CurrentGapType;
use Storm\Projector\Workflow\Notification\Promise\HasGap;
use Storm\Projector\Workflow\Notification\Promise\IsBatchStreamReset;

final class HandleStreamGap
{
    /**
     * When a gap is detected, we first, sleep for a while,
     * to let the remote storage to fix it.
     * Then we store the projection if some stream events
     * still need to be processed before the gap detection.
     *
     * @see InteractWithManagement@persistWhenThresholdIsReached
     */
    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        $hub->emitWhen(
            $hub->await(HasGap::class),
            function (NotificationHub $hub): void {
                /**
                 * Emit gap detected event before sleeping, as it may modify the gap type
                 *
                 * @todo more info on checkpoint, we could keep the last checkpoint in memory
                 *   to be retrieved when the gap is detected
                 */
                $hub->addEvent($hub->await(CurrentGapType::class)->value, fn () => null);

                $hub->emit(SleepOnGap::class);

                /**
                 * todo tests
                 */
                if (! $hub->await(IsBatchStreamReset::class)) {
                    $hub->emit(new ProjectionStored());
                }
            });

        return $next($hub);
    }
}
