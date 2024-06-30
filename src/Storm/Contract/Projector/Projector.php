<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Storm\Projector\Workflow\Watcher\ReportWatcher;

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
     * @return array{
     *      started_at: int<0, max>,
     *      elapsed_time: int<0, max>,
     *      ended_at: int<0, max>,
     *      cycle: int<0, max>,
     *      acked_event: int<0, max>,
     *      total_event: int<0, max>
     *  }
     *
     * @see ReportWatcher
     */
    public function getReport(): array;
}
