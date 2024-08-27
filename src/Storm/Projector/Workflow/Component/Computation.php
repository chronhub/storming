<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Options\Option;
use Storm\Projector\Support\ProjectionReport;
use Storm\Projector\Workflow\ComponentSubscriber;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Process;

/**
 * @template TComputation of array{
 *     projection_id: string,
 *     started_at: string,
 *     elapsed_time: float,
 *     ended_at: string,
 *     cycle: int<0, max>,
 *     acked_event: int<0, max>,
 *     total_event: int<0, max>,
 *     checkpoint: array,
 *     options: array<Option::*, null|string|int|bool|array>,
 * }
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
        'checkpoint' => [],
        'options' => [],
    ];

    public function subscribe(Process $process, ContextReader $context): void
    {
        $this->report['projection_id'] = $context->id();

        $process->addListener(BeforeWorkflowRenewal::class, function (Process $process, BeforeWorkflowRenewal $event): void {
            if ($event->isSprintTerminated) {
                $this->report['cycle'] = $process->metrics()->get('cycle');
                $this->report['acked_event'] = $process->metrics()->get('acked');
                $this->report['total_event'] = $process->metrics()->get('main');
                $this->report['started_at'] = $process->time()->getStartedTime()->format();
                $this->report['ended_at'] = $process->time()->getCurrentTime()->format();
                $this->report['elapsed_time'] = $process->time()->getElapsedTime();
                $this->report['checkpoint'] = $process->recognition()->jsonSerialize();
                $this->report['options'] = $process->option()->jsonSerialize();
            }
        });
    }

    public function report(): ProjectionReport
    {
        return new ProjectionReport($this->report);
    }
}
