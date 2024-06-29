<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector;

use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Workflow\Watcher\AckedStreamWatcher;
use Storm\Projector\Workflow\Watcher\BatchCounterWatcher;
use Storm\Projector\Workflow\Watcher\BatchStreamWatcher;
use Storm\Projector\Workflow\Watcher\CycleWatcher;
use Storm\Projector\Workflow\Watcher\EventStreamWatcher;
use Storm\Projector\Workflow\Watcher\MasterEventCounterWatcher;
use Storm\Projector\Workflow\Watcher\SprintWatcher;
use Storm\Projector\Workflow\Watcher\StopWatcher;
use Storm\Projector\Workflow\Watcher\TimeWatcher;
use Storm\Projector\Workflow\Watcher\UserStateWatcher;
use Storm\Projector\Workflow\Watcher\WatcherManager;

use function method_exists;

beforeEach(function () {
    $this->option = $this->createStub(ProjectionOption::class);
    $this->eventStreamProvider = $this->createStub(EventStreamProvider::class);
    $this->clock = $this->createStub(SystemClock::class);

    // configure the option stubs
    $this->option->method('getBlockSize')->willReturn(100);
    $this->option->method('getSleep')->willReturn([100, 100]);

    $this->watcherManager = new WatcherManager($this->option, $this->eventStreamProvider, $this->clock);
});

dataset('watchers', [
    'acked stream watcher' => ['ackedStream', AckedStreamWatcher::class, false],
    'batch counter watcher' => ['batchCounter', BatchCounterWatcher::class, false],
    'batch stream watcher' => ['batchStream', BatchStreamWatcher::class, false],
    'cycle watcher' => ['cycle', CycleWatcher::class, false],
    'master event counter watcher' => ['masterCounter', MasterEventCounterWatcher::class, false],
    'sprint watcher' => ['sprint', SprintWatcher::class, false],
    'stop watcher' => ['stop', StopWatcher::class, true],
    'event stream watcher' => ['streamDiscovery', EventStreamWatcher::class, false],
    'time watcher' => ['time', TimeWatcher::class, false],
    'user state watcher' => ['userState', UserStateWatcher::class, false],
]);

it('can access watcher property', function (string $property, string $className) {
    expect($this->watcherManager->{$property})->toBeInstanceOf($className);
})->with('watchers');

it('can subscribe to watchers', function (string $property, string $className, bool $hasSubscribeMethod) {
    $hub = $this->createStub(NotificationHub::class);
    $context = $this->createStub(ContextReader::class);

    expect(method_exists($this->watcherManager->{$property}, 'subscribe'))->toBe($hasSubscribeMethod);

    if ($hasSubscribeMethod) {
        $this->watcherManager->{$property}->subscribe($hub, $context);
    }
})->with('watchers');

it('raise exception when accessing non-existent watcher', function () {
    /** @phpstan-ignore-next-line */
    $this->watcherManager->nonExistentWatcher;
})->throws(InvalidArgumentException::class, 'Watcher nonExistentWatcher not found');
