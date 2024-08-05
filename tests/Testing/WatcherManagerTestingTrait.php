<?php

declare(strict_types=1);

namespace Storm\Tests\Testing;

use Mockery\MockInterface;
use Options\ProjectionOption;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Factory\WatcherFactory;
use Storm\Projector\Workflow\Component;
use Storm\Projector\Workflow\ComponentRegistry;

trait WatcherManagerTestingTrait
{
    protected Component&MockInterface $subscriptor;

    protected ProjectionOption&MockInterface $projectionOption;

    protected EventStreamProvider&MockInterface $eventStreamProvider;

    protected SystemClock&MockInterface $clock;

    protected Component $watcherManager;

    protected function setupWatcherManager(
        int $batchCounterBlockSize = 100,
        array $batchStreamSleep = [1, 1],
    ): void {
        $this->projectionOption = mock(ProjectionOption::class);
        $this->eventStreamProvider = mock(EventStreamProvider::class);
        $this->clock = mock(SystemClock::class);
        $this->subscriptor = mock(Component::class);

        // constructed projection option
        $this->projectionOption->shouldReceive('getBlockSize')->andReturn($batchCounterBlockSize);
        $this->projectionOption->shouldReceive('getSleep')->andReturn($batchStreamSleep);

        $factory = new WatcherFactory($this->projectionOption, $this->eventStreamProvider, $this->clock);

        $this->watcherManager = new ComponentRegistry($factory->watchers);

        $this->subscriptor->shouldReceive('watcher')->andReturn($this->watcherManager);
    }
}
