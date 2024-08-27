<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Factory;

use Options\ProjectionOption;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Factory\WatcherFactory;

use function array_keys;

beforeEach(function () {
    $this->option = mock(ProjectionOption::class);
    $this->eventStreamProvider = mock(EventStreamProvider::class);
    $this->clock = mock(SystemClock::class);

    // configure the option stubs
    $this->option->shouldReceive('getBlockSize')->andReturn(100);
    $this->option->shouldReceive('getSleep')->andReturn([1000, 5, 10000]);
    $this->option->shouldReceive('getRetries')->andReturn([1]);
    $this->option->shouldReceive('getRecordGap')->andReturn(false);

    $this->watcherFactory = new WatcherFactory($this->option, $this->eventStreamProvider, $this->clock);
});

test('assert watchers keys', function () {
    expect(array_keys($this->watcherFactory->watchers))->toBe(
        [
            'discovery',
            'recognition',
            'report',
            'sprint',
            'stop',
            'streamEvent',
            'time',
            'userState',
        ]
    );
});
