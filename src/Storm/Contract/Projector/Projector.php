<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Workflow\Component\Computation;

/**
 * @phpstan-template TReport of array{
 *      started_at: int<0, max>,
 *      elapsed_time: int<0, max>,
 *      ended_at: int<0, max>,
 *      cycle: int<0, max>,
 *      acked_event: int<0, max>,
 *      total_event: int<0, max>,
 *      last_checkpoint?: array
 *  }
 */
interface Projector
{
    /**
     * Run in the background or once.
     */
    public function run(bool $inBackground): void;

    /**
     * Get the projection state.
     */
    public function getState(): array;

    /**
     * Get the projection report.
     *
     * @return array<TReport>
     *
     *@see Computation
     */
    public function getReport(): array;
}
