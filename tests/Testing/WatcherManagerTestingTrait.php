<?php

declare(strict_types=1);

namespace Storm\Tests\Testing;

use Mockery\MockInterface;
use Options\ProjectionOption;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Factory\Component\ComponentManager;
use Storm\Projector\Factory\Component\Components;
use Storm\Projector\Factory\WatcherFactory;

trait WatcherManagerTestingTrait
{
    protected Components&MockInterface $subscriptor;

    protected ProjectionOption&MockInterface $projectionOption;

    protected EventStreamProvider&MockInterface $eventStreamProvider;

    protected SystemClock&MockInterface $clock;

    protected Components $watcherManager;

    protected function setupWatcherManager(
        int $batchCounterBlockSize = 100,
        array $batchStreamSleep = [1, 1],
    ): void {
        $this->projectionOption = mock(ProjectionOption::class);
        $this->eventStreamProvider = mock(EventStreamProvider::class);
        $this->clock = mock(SystemClock::class);
        $this->subscriptor = mock(Components::class);

        // constructed projection option
        $this->projectionOption->shouldReceive('getBlockSize')->andReturn($batchCounterBlockSize);
        $this->projectionOption->shouldReceive('getSleep')->andReturn($batchStreamSleep);

        $factory = new WatcherFactory($this->projectionOption, $this->eventStreamProvider, $this->clock);

        $this->watcherManager = new ComponentManager($factory->watchers);

        $this->subscriptor->shouldReceive('watcher')->andReturn($this->watcherManager);
    }
}
