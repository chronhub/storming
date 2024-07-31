<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Closure;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Workflow\Component\HaltOn;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\Process;

use function is_int;

/**
 * @template TProcess of Process
 *
 * Stop the projection when a certain condition is met.
 * The stopping process can only occur after a cycle was completed,
 * and the ShouldTerminateWorkflow notification was dispatched.
 *
 * @see HaltOn
 * @see ShouldTerminateWorkflow
 *
 * @example
 *   <code>
 *      $projector
 *          ->haltOn(StopWhen::cycleReached(10)): bool
 *          ->haltOn(StopWhen::timeExpired(1672531200)): bool
 *          ->haltOn(StopWhen::batchStreamBlank(10)): bool
 *          ->haltOn(StopWhen::gapDetected(GapType::UNRECOVERABLE_GAP)): bool
 *          ->haltOn(Closure(Process) $callback): bool
 *    </code>
 */
class StopWhen
{
    /**
     * Stop the projection when the current cycle is reached.
     *
     * @param  positive-int            $cycle
     * @return Closure(TProcess): bool
     */
    public static function cycleReached(int $cycle): Closure
    {
        /** @phpstan-ignore-next-line */
        if ($cycle < 1) {
            throw new InvalidArgumentException('"Stop when" cycle reached must be greater than 0');
        }

        return function (Process $process) use ($cycle): bool {
            $currentCycle = $process->metrics()->cycle;

            return $currentCycle >= $cycle;
        };
    }

    /**
     * Stop the projection when the current expiration time is reached.
     * fixMe use interval, running again will just stop the projection
     *
     * @param  positive-int            $expiredAt unix timestamp
     * @return Closure(TProcess): bool
     */
    public static function timeExpired(int $expiredAt): Closure
    {
        /** @phpstan-ignore-next-line */
        if ($expiredAt < 1) {
            throw new InvalidArgumentException('"Stop when" time must be greater than 0');
        }

        return function (Process $process) use ($expiredAt): bool {
            $currentTime = $process->time()->getCurrentTimestamp();

            return $currentTime >= $expiredAt;
        };
    }

    /**
     * Stop the projection when the batch stream is blank
     * after the specified number of cycles when provided.
     *
     * A blank batch stream has no events to process or to store.
     *
     * todo reset acked event on cycle renewedState
     *
     * @param  positive-int|null       $afterCycle
     * @return Closure(TProcess): bool
     */
    public static function batchStreamBlank(?int $afterCycle = null): Closure
    {
        /** @phpstan-ignore-next-line */
        if (is_int($afterCycle) && $afterCycle < 1) {
            throw new InvalidArgumentException('"After cycle" must be greater than 0');
        }

        return function (Process $process) use ($afterCycle): bool {
            if (is_int($afterCycle)) {
                $currentCycle = $process->metrics()->cycle;

                if ($afterCycle < $currentCycle) {
                    return false;
                }
            }

            return $process->metrics()->isBatchStreamBlank();
        };
    }

    /**
     * Stop the projection when a gap is detected before a checkpoint is saved.
     * Require a projection with retries configured.
     *
     * @return Closure(TProcess): bool
     *
     *@see GapType
     */
    public static function gapDetected(GapType $gapType): Closure
    {
        return function (Process $process) use ($gapType): bool {
            return $process->dispatcher()->wasEmittedOnce($gapType->value);
        };
    }
}
