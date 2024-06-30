<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Watcher;

use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Cycle\BeforeCycleRenewed;
use Storm\Projector\Workflow\Notification\Cycle\CurrentCycle;
use Storm\Projector\Workflow\Notification\MasterCounter\CurrentMasterCount;
use Storm\Projector\Workflow\Notification\MasterCounter\ShouldResetMasterEventCounter;
use Storm\Projector\Workflow\Notification\Sprint\SprintTerminated;
use Storm\Projector\Workflow\Notification\Stream\CountEventAcked;
use Storm\Projector\Workflow\Notification\Timer\GetCurrentTime;
use Storm\Projector\Workflow\Notification\Timer\GetElapsedTime;
use Storm\Projector\Workflow\Notification\Timer\GetStartedTime;

class ReportWatcher
{
    private array $report = [
        'started_at' => 0,
        'elapsed_time' => 0,
        'ended_at' => 0,
        'cycle' => 0,
        'acked_event' => 0,
        'total_event' => 0,
    ];

    public function subscribe(NotificationHub $hub, ContextReader $context): void
    {
        $hub->addListener(SprintTerminated::class, function (NotificationHub $hub) {
            $this->report['started_at'] = $hub->expect(GetStartedTime::class);
            $this->report['elapsed_time'] = $hub->expect(GetElapsedTime::class);
            $this->report['ended_at'] = $hub->expect(GetCurrentTime::class);
            $this->report['cycle'] = $hub->expect(CurrentCycle::class);
        });

        $hub->addListener(BeforeCycleRenewed::class, function (NotificationHub $hub) {
            // acked events are reset after each cycle
            $this->report['acked_event'] += $hub->expect(CountEventAcked::class);

            // the main event counter is reset on demand (when projection instance is running again)
            $shouldReset = $hub->expect(ShouldResetMasterEventCounter::class);
            $count = $hub->expect(CurrentMasterCount::class);

            $shouldReset
                ? $this->report['total_event'] += $count
                : $this->report['total_event'] = $count;
        });
    }

    /**
     * @return array{
     *     started_at: int<0, max>,
     *     elapsed_time: int<0, max>,
     *     ended_at: int<0, max>,
     *     cycle: int<0, max>,
     *     acked_event: int<0, max>,
     *     total_event: int<0, max>
     * }
     */
    public function getReport(): array
    {
        return $this->report;
    }
}
