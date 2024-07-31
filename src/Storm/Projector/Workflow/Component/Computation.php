<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

use Storm\Contract\Projector\ComponentSubscriber;
use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Process;

/**
 * @template TComputation of array{
 *     projection_id: string,
 *     started_at: int<0, max>,
 *     elapsed_time: int<0, max>,
 *     ended_at: int<0, max>,
 *     cycle: int<0, max>,
 *     acked_event: int<0, max>,
 *     total_event: int<0, max>,
 *     last_checkpoint: array
 * }
 *     todo import template
 */
class Computation implements ComponentSubscriber
{
    /**
     * @param TComputation $report
     */
    protected array $report = [
        'projection_id' => '',
        'started_at' => 0,
        'elapsed_time' => 0,
        'ended_at' => 0,
        'cycle' => 0,
        'acked_event' => 0,
        'total_event' => 0,
        'last_checkpoint' => null,
    ];

    public function subscribe(Process $process, ContextReader $context): void
    {
        $this->report['projection_id'] = $context->id();

        $process->addListener(BeforeWorkflowRenewal::class, function (Process $process): void {
            // fixMe: to array
            $this->report['cycle'] = $process->metrics()->cycle;
            $this->report['acked_event'] = $process->metrics()->acked;
            $this->report['total_event'] = $process->metrics()->main;

            $this->report['started_at'] = $process->time()->getStartedTime();
            $this->report['ended_at'] = $process->time()->getCurrentTimestamp();
            $this->report['elapsed_time'] = $this->report['ended_at'] - $this->report['started_at'];

            $this->report['last_checkpoint'] = $process->recognition()->jsonSerialize();
        });
    }

    /**
     * Return computation report.
     */
    public function report(): array
    {
        return $this->report;
    }
}
