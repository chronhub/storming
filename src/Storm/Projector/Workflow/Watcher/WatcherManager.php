<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Watcher;

use AllowDynamicProperties;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Support\Token\ConsumeWithSleepToken;
use Storm\Projector\Workflow\Timer;

use function method_exists;

/**
 * @property AckedStreamWatcher        $ackedStream
 * @property BatchCounterWatcher       $batchCounter
 * @property BatchStreamWatcher        $batchStream
 * @property CycleWatcher              $cycle
 * @property EventStreamWatcher        $streamDiscovery
 * @property MasterEventCounterWatcher $masterCounter
 * @property SnapshotWatcher           $snapshot
 * @property SprintWatcher             $sprint
 * @property StopWatcher               $stop
 * @property TimeWatcher               $time
 * @property UserStateWatcher          $userState
 *
 * checkMe allow dynamic properties attribute is only meant for phpStan
 */
#[AllowDynamicProperties]
class WatcherManager
{
    private array $watchers = [];

    public function __construct(
        protected ProjectionOption $option,
        protected EventStreamProvider $eventStreamProvider,
        protected SystemClock $clock
    ) {
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
    }

    public function subscribe(NotificationHub $hub, ContextReader $context): void
    {
        foreach ($this->watchers as $watcher) {
            if (method_exists($watcher, 'subscribe')) {
                $watcher->subscribe($hub, $context);
            }
        }
    }

    /**
     * @throws InvalidArgumentException when watcher not found
     */
    public function get(string $name): ?object
    {
        if (isset($this->watchers[$name])) {
            return $this->watchers[$name];
        }

        throw new InvalidArgumentException("Watcher $name not found");
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
