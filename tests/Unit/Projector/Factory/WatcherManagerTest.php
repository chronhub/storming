<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector;

use BadMethodCallException;
use Checkpoint\CheckpointRecognition;
use Options\ProjectionOption;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Factory\WatcherFactory;
use Storm\Projector\Workflow\Component;
use Storm\Projector\Workflow\Component\Computation;
use Storm\Projector\Workflow\Component\EventStreamBatch;
use Storm\Projector\Workflow\Component\EventStreamDiscovery;
use Storm\Projector\Workflow\Component\HaltOn;
use Storm\Projector\Workflow\Component\Runner;
use Storm\Projector\Workflow\Component\Timer;
use Storm\Projector\Workflow\Component\UserState;

use function method_exists;

beforeEach(function () {
    $this->option = mock(ProjectionOption::class);
    $this->eventStreamProvider = mock(EventStreamProvider::class);
    $this->clock = mock(SystemClock::class);

    // configure the option stubs
    $this->option->shouldReceive('getBlockSize')->andReturn(100);
    $this->option->shouldReceive('getSleep')->andReturn([1000, 10, 10000]);
    $this->option->shouldReceive('getRetries')->andReturn([1]);
    $this->option->shouldReceive('getRecordGap')->andReturn(false);

    $watcherFactory = new WatcherFactory($this->option, $this->eventStreamProvider, $this->clock);

    $this->watcherManager = new Component($watcherFactory->watchers);
});

/**
 * property watcher, class name, has "subscribe" method name
 */
dataset('watchers', [
    'batch stream watcher' => ['streamEvent', EventStreamBatch::class, false],
    'sprint watcher' => ['sprint', Runner::class, false],
    'stop watcher' => ['stop', HaltOn::class, true],
    'event stream watcher' => ['discovery', EventStreamDiscovery::class, false],
    'time watcher' => ['time', Timer::class, false],
    'user state watcher' => ['userState', UserState::class, false],
    'checkpoint recognition watcher' => ['recognition', CheckpointRecognition::class, false],
    'report watcher' => ['report', Computation::class, true],
]);

test('can access watcher property', function (string $method, string $className) {
    expect($this->watcherManager->{$method}())->toBeInstanceOf($className);
})->with('watchers');

test('can subscribe to watcher when method exists', function (string $method, string $className, bool $hasSubscribeMethod) {
    $hub = mock(NotificationHub::class)->shouldIgnoreMissing();
    $context = mock(ContextReader::class)->shouldIgnoreMissing();

    expect(method_exists($this->watcherManager->{$method}(), 'subscribe'))->toBe($hasSubscribeMethod);

    if ($hasSubscribeMethod) {
        $this->watcherManager->{$method}()->subscribe($hub, $context);
    }
})->with('watchers');

test('can subscribe through the manager when method exists', function (string $method, string $className, bool $hasSubscribeMethod) {
    $hub = mock(NotificationHub::class)->shouldIgnoreMissing();
    $context = mock(ContextReader::class)->shouldIgnoreMissing();

    expect(method_exists($this->watcherManager->{$method}(), 'subscribe'))->toBe($hasSubscribeMethod);

    if ($hasSubscribeMethod) {
        $this->watcherManager->subscribe($hub, $context);
    }
})->with('watchers');

test('raise exception when accessing non-existent watcher', function () {
    /** @phpstan-ignore-next-line */
    $this->watcherManager->nonExistentWatcher();
})->throws(BadMethodCallException::class, 'Watcher nonExistentWatcher not found');
