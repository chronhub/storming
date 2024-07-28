<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow;

use Closure;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Workflow\Agent\StopAgent;
use Storm\Projector\Workflow\Notification\GapDetected;
use Storm\Projector\Workflow\Notification\Promise\CurrentTime;
use Storm\Projector\Workflow\Notification\Promise\CurrentWorkflowCycle;
use Storm\Projector\Workflow\Notification\Promise\IsBatchStreamBlank;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;

use function is_int;

/**
 * Stop the projection when a certain condition is met.
 * The stopping process can only occur after a cycle was completed.
 *
 * @see StopAgent
 * @see ShouldTerminateWorkflow
 *
 * @example
 *   <code>
 *      $projector
 *          ->haltOn(StopWhen::cycleReached(10)): bool
 *          ->haltOn(StopWhen::timeExpired(1672531200)): bool
 *          ->haltOn(StopWhen::batchStreamBlank(10)): bool
 *          ->haltOn(StopWhen::gapDetected(GapType::UNRECOVERABLE_GAP)): bool
 *          ->haltOn(Closure(NotificationHub) $callback): bool
 *    </code>
 */
class StopWhen
{
    /**
     * Stop the projection when the current cycle is reached.
     *
     * @param positive-int $cycle
     */
    public static function cycleReached(int $cycle): Closure
    {
        /** @phpstan-ignore-next-line */
        if ($cycle < 1) {
            throw new InvalidArgumentException('"Stop when" cycle must be greater than 0');
        }

        return fn (NotificationHub $hub): bool => $hub->await(CurrentWorkflowCycle::class) === $cycle;
    }

    /**
     * Stop the projection when the current expiration time is reached.
     *
     * @param positive-int $expiredAt unix timestamp
     */
    public static function timeExpired(int $expiredAt): Closure
    {
        /** @phpstan-ignore-next-line */
        if ($expiredAt < 1) {
            throw new InvalidArgumentException('"Stop when" time must be greater than 0');
        }

        return fn (NotificationHub $hub): bool => $hub->await(CurrentTime::class) >= $expiredAt;

    }

    /**
     * Stop the projection when the batch stream is blank
     * after the specified number of cycles when provided.
     *
     * A blank batch stream has no events to process or to store.
     *
     * @see RotationEventMap todo reset acked event on cycle renewed
     * @see IsBatchStreamBlank
     *
     * @param positive-int|null $afterCycle
     */
    public static function batchStreamBlank(?int $afterCycle = null): Closure
    {
        /** @phpstan-ignore-next-line */
        if (is_int($afterCycle) && $afterCycle < 1) {
            throw new InvalidArgumentException('"After cycle" must be greater than 0');
        }

        return function (NotificationHub $hub) use ($afterCycle): bool {
            if (is_int($afterCycle)) {
                $currentCycle = $hub->await(CurrentWorkflowCycle::class);

                if ($afterCycle < $currentCycle) {
                    return false;
                }
            }

            return $hub->await(IsBatchStreamBlank::class);
        };
    }

    /**
     * Stop the projection when a gap is detected,
     * before a checkpoint is saved.
     *
     * Require a projection with retries configured.
     *
     * @see GapType
     */
    public static function gapDetected(GapType $gapType): Closure
    {
        return function (NotificationHub $hub) use ($gapType): bool {
            return $hub->hasEvent($gapType->value);
        };
    }
}
