<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Watcher;

use AllowDynamicProperties;
use ArrayAccess;
use BadMethodCallException;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Factory\WatcherFactory;

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
class WatcherManager implements ArrayAccess
{
    public function __construct(protected WatcherFactory $factory)
    {
    }

    public function subscribe(NotificationHub $hub, ContextReader $context): void
    {
        foreach ($this->factory->watchers() as $watcher) {
            if (method_exists($watcher, 'subscribe')) {
                $watcher->subscribe($hub, $context);
            }
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->factory->get($offset) !== null;
    }

    public function offsetGet(mixed $offset): object
    {
        return $this->factory->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('WatcherManager is readonly');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('WatcherManager is readonly');
    }
}
