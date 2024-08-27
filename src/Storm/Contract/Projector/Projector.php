<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Support\ProjectionReport;
use Storm\Projector\Workflow\Component\Computation;

interface Projector
{
    /**
     * Run in the background or once.
     */
    public function run(bool $inBackground): void;

    /**
     * Stop the projection.
     *
     * It does not free the projection from his lock,
     * so you can restart it later, after the lock has expired.
     */
    public function stop(): void;

    /**
     * Get the projection state.
     */
    public function getState(): array;

    /**
     * Get the projection report.
     *
     * @see Computation
     */
    public function getReport(): ProjectionReport;
}
