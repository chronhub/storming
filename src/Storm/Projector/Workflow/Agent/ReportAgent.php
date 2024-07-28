<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Agent;

use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ShouldAgentSubscribe;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Notification\Promise\CurrentAckedCount;
use Storm\Projector\Workflow\Notification\Promise\CurrentMainCount;
use Storm\Projector\Workflow\Notification\Promise\CurrentStartedTime;
use Storm\Projector\Workflow\Notification\Promise\CurrentTime;
use Storm\Projector\Workflow\Notification\Promise\CurrentWorkflowCycle;

/**
 * @template TReport of array{
 *     projection_id: string,
 *     started_at: int<0, max>,
 *     elapsed_time: int<0, max>,
 *     ended_at: int<0, max>,
 *     cycle: int<0, max>,
 *     acked_event: int<0, max>,
 *     total_event: int<0, max>
 * }
 */
class ReportAgent implements ShouldAgentSubscribe
{
    /**
     * @param TReport $report
     */
    protected array $report = [
        'projection_id' => '',
        'started_at' => 0,
        'elapsed_time' => 0,
        'ended_at' => 0,
        'cycle' => 0,
        'acked_event' => 0,
        'total_event' => 0,
    ];

    public function subscribe(NotificationHub $hub, ContextReader $context): void
    {
        $hub->addEvent(BeforeWorkflowRenewal::class, function (NotificationHub $hub) use ($context): void {
            $this->report['projection_id'] = $context->id();
            $this->report['cycle'] = $hub->await(CurrentWorkflowCycle::class);
            $this->report['started_at'] = $hub->await(CurrentStartedTime::class);
            $this->report['ended_at'] = $hub->await(CurrentTime::class);
            $this->report['elapsed_time'] = $this->report['ended_at'] - $this->report['started_at'];
            $this->report['acked_event'] = $hub->await(CurrentAckedCount::class);
            $this->report['total_event'] = $hub->await(CurrentMainCount::class);
        });
    }

    /**
     * Return the projection report.
     */
    public function getReport(): array
    {
        return $this->report;
    }
}
