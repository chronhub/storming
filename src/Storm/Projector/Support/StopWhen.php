<?php

declare(strict_types=1);

namespace Storm\Projector\Support;

use Closure;
use DateInterval;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Factory\Component\HaltOn;
use Storm\Projector\Workflow\Notification\ShouldTerminateWorkflow;
use Storm\Projector\Workflow\Process;

/**
 * @template TProcess of Process
 *
 * Stop the projection when a certain condition is met.
 *
 * The stopping process can only occur after a cycle was completed,
 * and react on ShouldTerminateWorkflow notification was dispatched.
 *
 * @see HaltOn
 * @see ShouldTerminateWorkflow
 *
 * @example
 *   <code>
 *      $projector
 *          ->haltOn(StopWhen::cycleReached(10)): bool
 *          ->haltOn(StopWhen::timeExpired('1', 'minute')): bool
 *          ->haltOn(StopWhen::gapDetected(GapType::UNRECOVERABLE_GAP)): bool
 *          ->haltOn(\Closure(Process) $callback): bool
 *    </code>
 */
class StopWhen
{
    /**
     * Stop the projection when the current cycle is reached.
     *
     * @param  positive-int            $cycle
     * @return Closure(TProcess): bool
     *
     * @throws InvalidArgumentException when the cycle is less than 1
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
     *
     * @see CarbonImmutable::add()
     *
     * @example $date->add('minutes', 15) // 15 minutes
     * @example $date->add(CarbonInterval::days(4)) // 4 days
     * @example $date->add('hour', 3) // 3 hours
     *
     * @return Closure(TProcess): bool
     */
    public static function timeExpired(string|DateInterval $interval, int|float $unit): Closure
    {
        return function (Process $process) use ($interval, $unit): bool {
            $expiredAt = $process->time()->getStartedTime()->add($interval, $unit);

            return $process->time()->getCurrentTime()->isGreaterThan($expiredAt);
        };
    }

    /**
     * Stop the projection when no stream events have been loaded.
     *
     * @return Closure(TProcess): bool
     */
    public static function emptyBatch(): Closure
    {
        return function (Process $process): bool {
            return $process->batch()->wasEmpty();
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
