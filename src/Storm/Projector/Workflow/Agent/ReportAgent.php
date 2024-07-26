<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Agent;

use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ShouldAgentSubscribe;
use Storm\Projector\Support\AckedCounter;
use Storm\Projector\Support\CycleCounter;
use Storm\Projector\Support\MainCounter;
use Storm\Projector\Support\ProcessedCounter;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Notification\Promise\CurrentStartedTime;
use Storm\Projector\Workflow\Notification\Promise\CurrentTime;

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

    public function __construct(
        protected readonly MainCounter $mainCounter,
        protected readonly ProcessedCounter $processedCounter,
        protected readonly AckedCounter $ackedCounter,
        protected readonly CycleCounter $cycleCounter,
    ) {}

    public function subscribe(NotificationHub $hub, ContextReader $context): void
    {
        $hub->addEvent(BeforeWorkflowRenewal::class, function (NotificationHub $hub) use ($context): void {
            $this->report['projection_id'] = $context->id();
            $this->report['cycle'] = $this->cycleCounter->current();
            $this->report['started_at'] = $hub->await(CurrentStartedTime::class);
            $this->report['ended_at'] = $hub->await(CurrentTime::class);
            $this->report['elapsed_time'] = $this->report['ended_at'] - $this->report['started_at'];
            $this->report['acked_event'] = $this->ackedCounter->count();

            // fixMe used by stopWatcher removed
            // the main event counter is reset on demand
            // when projection instance is running again
            $shouldReset = $this->main()->isDoNotReset();
            $count = $this->mainCounter->current();

            $shouldReset
                ? $this->report['total_event'] += $count
                : $this->report['total_event'] = $count;
        });
    }

    /**
     * Return the main stream event counter.
     */
    public function main(): MainCounter
    {
        return $this->mainCounter;
    }

    /**
     * Return the processed stream event counter.
     */
    public function processed(): ProcessedCounter
    {
        return $this->processedCounter;
    }

    /**
     * Return the acked stream event counter.
     */
    public function acked(): AckedCounter
    {
        return $this->ackedCounter;
    }

    /**
     * Return the cycle workflow counter.
     */
    public function cycle(): CycleCounter
    {
        return $this->cycleCounter;
    }

    /**
     * Return the projection report.
     */
    public function getReport(): array
    {
        return $this->report;
    }
}
