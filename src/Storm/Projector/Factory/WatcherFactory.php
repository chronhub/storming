<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Projector\Support\Token\ConsumeWithSleepToken;
use Storm\Projector\Workflow\Timer;
use Storm\Projector\Workflow\Watcher\AckedStreamWatcher;
use Storm\Projector\Workflow\Watcher\BatchCounterWatcher;
use Storm\Projector\Workflow\Watcher\BatchStreamWatcher;
use Storm\Projector\Workflow\Watcher\CycleWatcher;
use Storm\Projector\Workflow\Watcher\EventStreamWatcher;
use Storm\Projector\Workflow\Watcher\MasterEventCounterWatcher;
use Storm\Projector\Workflow\Watcher\SnapshotWatcher;
use Storm\Projector\Workflow\Watcher\SprintWatcher;
use Storm\Projector\Workflow\Watcher\StopWatcher;
use Storm\Projector\Workflow\Watcher\TimeWatcher;
use Storm\Projector\Workflow\Watcher\UserStateWatcher;

class WatcherFactory
{
    /**
     * @var array Watchers<string, object>
     */
    protected array $watchers = [];

    public function make(ProjectionOption $option, EventStreamProvider $eventStreamProvider, SystemClock $clock): static
    {
        $this->watchers['ackedStream'] = new AckedStreamWatcher();
        $this->watchers['batchCounter'] = new BatchCounterWatcher($option->getBlockSize());
        $this->watchers['batchStream'] = $this->batchStreamWatcher($option);
        $this->watchers['cycle'] = new CycleWatcher();
        $this->watchers['masterCounter'] = new MasterEventCounterWatcher();
        $this->watchers['snapshot'] = $this->snapshotWatcher($option, $clock);
        $this->watchers['sprint'] = new SprintWatcher();
        $this->watchers['stop'] = new StopWatcher();
        $this->watchers['streamDiscovery'] = new EventStreamWatcher($eventStreamProvider);
        $this->watchers['time'] = new TimeWatcher(new Timer($clock));
        $this->watchers['userState'] = new UserStateWatcher();

        return $this;
    }

    public function get(string $name): ?object
    {
        return $this->watchers[$name] ?? null;
    }

    /**
     * @return array<string, object>
     */
    public function watchers(): array
    {
        return $this->watchers;
    }

    protected function batchStreamWatcher(ProjectionOption $option): BatchStreamWatcher
    {
        [$capacity, $rate] = $option->getSleep();

        $bucket = new ConsumeWithSleepToken($capacity, $rate);

        return new BatchStreamWatcher($bucket);
    }

    protected function snapshotWatcher(ProjectionOption $option, SystemClock $clock): SnapshotWatcher
    {
        $interval = $option->getSnapshotInterval();

        return new SnapshotWatcher($clock, $interval['position'], $interval['time'], $interval['usleep']);
    }
}
