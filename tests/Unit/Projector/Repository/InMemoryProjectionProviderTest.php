<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use Closure;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Projector\Exception\InMemoryProjectionFailed;
use Storm\Projector\Exception\ProjectionAlreadyExists;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\Repository\Data\CreateData;
use Storm\Projector\Repository\Data\PersistData;
use Storm\Projector\Repository\Data\ProjectionData;
use Storm\Projector\Repository\Data\ReleaseData;
use Storm\Projector\Repository\Data\ResetData;
use Storm\Projector\Repository\Data\StartAgainData;
use Storm\Projector\Repository\Data\StartData;
use Storm\Projector\Repository\Data\StopData;
use Storm\Projector\Repository\Data\UpdateLockData;
use Storm\Projector\Repository\Data\UpdateStatusData;
use Storm\Projector\Repository\InMemoryProjectionProvider;

beforeEach(function () {
    $this->clock = mock(SystemClock::class);
    $this->provider = new InMemoryProjectionProvider($this->clock);
});

dataset('projection data', [
    'persist' => fn () => new PersistData('{"updated"}', '{"updated"}', '2024-01-01 00:00:00'),
    'release' => fn () => new ReleaseData('idle', null),
    'reset' => fn () => new ResetData('stop', '{"updated"}', '{"updated"}'),
    'start again' => fn () => new StartAgainData('run', '2025-01-01 00:00:00'),
    'stop' => fn () => new StopData('idle', '{"updated"}', '{"updated"}', '2024-01-01 00:00:00'),
    'update lock' => fn () => new UpdateLockData('2025-01-01 00:00:00'),
    'update status' => fn () => new UpdateStatusData('run'),
]);

function createProjection(string $stream, string $status): Closure
{
    return function ($test) use ($stream, $status) {
        expect($test->provider->exists($stream))->toBeFalse();

        $data = new CreateData($status);
        $test->provider->createProjection($stream, $data);

        expect($test->provider->exists($stream))->toBeTrue();
    };
}

test('default instance', function () {
    expect($this->provider)->toBeInstanceOf(ProjectionProvider::class)
        ->and($this->provider->exists('stream1'))->toBeFalse()
        ->and($this->provider->retrieve('stream1'))->toBeNull();
});

test('create projection', function () {
    createProjection('stream1', 'run')($this);

    $projection = $this->provider->retrieve('stream1');

    expect($projection->name())->toBe('stream1')
        ->and($projection->status())->toBe('run')
        ->and($projection->state())->toBe('{}')
        ->and($projection->checkpoint())->toBe('{}')
        ->and($projection->lockedUntil())->toBeNull();
});

test('raise exception when projection already exists', function () {
    createProjection('stream1', 'idle')($this);

    $this->provider->createProjection('stream1', new CreateData('run'));
})->throws(ProjectionAlreadyExists::class);

test('acquire lock for existing projection with null lock', function () {
    $this->clock->shouldNotReceive('isGreaterThanNow');

    createProjection('stream1', 'run')($this);

    expect($this->provider->retrieve('stream1')->lockedUntil())->toBeNull();

    $updateLock = new UpdateLockData('2024-01-01 00:00:00');
    $this->provider->acquireLock('stream1', $updateLock);

    $projection = $this->provider->retrieve('stream1');

    expect($projection->name())->toBe('stream1')
        ->and($projection->status())->toBe('run')
        ->and($projection->state())->toBe('{}')
        ->and($projection->checkpoint())->toBe('{}')
        ->and($projection->lockedUntil())->toBe('2024-01-01 00:00:00');
});

test('acquire lock for existing projection when now is greater than lock', function () {
    $this->clock->shouldReceive('isGreaterThanNow')->andReturn(true)->once();

    $createProjection = new CreateData('run');
    $this->provider->createProjection('stream1', $createProjection);

    $updateProjection = new StartData('run', '2024-01-01 00:00:00');
    $this->provider->updateProjection('stream1', $updateProjection);

    expect($this->provider->retrieve('stream1')->lockedUntil())->toBe('2024-01-01 00:00:00');

    $acquireLock = new StartData('run', '2025-01-01 00:00:00');
    $this->provider->acquireLock('stream1', $acquireLock);

    expect($this->provider->retrieve('stream1')->lockedUntil())->toBe('2025-01-01 00:00:00');
});

test('raise projection already running exception when it can not acquired lock', function () {
    $this->clock->shouldReceive('isGreaterThanNow')->andReturn(false)->once();

    createProjection('stream1', 'run')($this);

    $startProjection = new StartData('run', '2024-01-01 00:00:00');
    $this->provider->updateProjection('stream1', $startProjection);

    expect($this->provider->retrieve('stream1')->lockedUntil())->toBe('2024-01-01 00:00:00');

    $acquireLock = new StartData('run', '2025-01-01 00:00:00');
    $this->provider->acquireLock('stream1', $acquireLock);
})->throws(ProjectionAlreadyRunning::class);

test('update projection', function (ProjectionData $data) {
    $this->clock->shouldNotReceive('isGreaterThanNow');

    createProjection('stream1', 'run')($this);

    $this->provider->updateProjection('stream1', $data);

    $projection = $this->provider->retrieve('stream1');
    $updated = $data->toArray();

    expect($projection->name())->toBe('stream1')
        ->and($projection->status())->toBe($updated['status'] ?? 'run')
        ->and($projection->state())->toBe($updated['state'] ?? '{}')
        ->and($projection->checkpoint())->toBe($updated['checkpoint'] ?? '{}')
        ->and($projection->lockedUntil())->toBe($updated['locked_until'] ?? null);
})->with('projection data');

test('raise exception when updating projection with empty data', function () {
    createProjection('stream1', 'run')($this);

    $data = new readonly class extends ProjectionData
    {
        public function toArray(): array
        {
            return [];
        }
    };

    $this->provider->updateProjection('stream1', $data);
})->throws(InMemoryProjectionFailed::class, 'Provide at least one change to update projection stream1');

test('raise projection not found exception when update projection for non existing projection', function (ProjectionData $data) {
    $this->provider->updateProjection('stream1', $data);
})
    ->with('projection data')
    ->throws(ProjectionNotFound::class);

test('delete projection by name', function (array $streams) {
    foreach ($streams as $stream) {
        createProjection($stream, 'run')($this);
    }

    expect($this->provider->exists($streams[0]))->toBeTrue()
        ->and($this->provider->exists($streams[1]))->toBeTrue();

    $this->provider->deleteProjection($streams[0]);

    expect($this->provider->exists($streams[0]))->toBeFalse()
        ->and($this->provider->exists($streams[1]))->toBeTrue();

    $this->provider->deleteProjection($streams[1]);

    expect($this->provider->exists($streams[0]))->toBeFalse()
        ->and($this->provider->exists($streams[1]))->toBeFalse();
})->with(['streams' => [['stream1', 'stream2']]]);

test('raise exception when delete non existing projection', function () {
    expect($this->provider->exists('stream1'))->toBeFalse();

    $this->provider->deleteProjection('stream1');
})->throws(ProjectionNotFound::class);

test('filter projection by names', function (array $streams) {
    foreach ($streams as $stream) {
        createProjection($stream, 'run')($this);
    }

    expect($this->provider->filterByNames('stream1', 'foo', 'bar'))->toBe(['stream1'])
        ->and($this->provider->filterByNames('stream2', 'foo', 'stream1'))->toBe(['stream2', 'stream1']);

})->with([['many streams' => ['stream2', 'stream1']]]);
