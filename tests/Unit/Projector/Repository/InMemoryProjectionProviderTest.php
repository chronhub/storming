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
    $this->clock = $this->createMock(SystemClock::class);
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

it('test instance', function () {
    expect($this->provider)->toBeInstanceOf(ProjectionProvider::class)
        ->and($this->provider->exists('stream-1'))->toBeFalse()
        ->and($this->provider->retrieve('stream-1'))->toBeNull();
});

it('create projection', function () {
    createProjection('stream-1', 'run')($this);

    $projection = $this->provider->retrieve('stream-1');

    expect($projection->name())->toBe('stream-1')
        ->and($projection->status())->toBe('run')
        ->and($projection->state())->toBe('{}')
        ->and($projection->checkpoint())->toBe('{}')
        ->and($projection->lockedUntil())->toBeNull();
});

it('raise exception when projection already exists', function () {
    createProjection('stream-1', 'idle')($this);

    $this->provider->createProjection('stream-1', new CreateData('run'));
})->throws(ProjectionAlreadyExists::class);

it('acquire lock for existing projection with null lock', function () {
    $this->clock->expects($this->never())->method('isGreaterThanNow');

    createProjection('stream-1', 'run')($this);

    expect($this->provider->retrieve('stream-1')->lockedUntil())->toBeNull();

    $updateLock = new UpdateLockData('2024-01-01 00:00:00');
    $this->provider->acquireLock('stream-1', $updateLock);

    $projection = $this->provider->retrieve('stream-1');

    expect($projection->name())->toBe('stream-1')
        ->and($projection->status())->toBe('run')
        ->and($projection->state())->toBe('{}')
        ->and($projection->checkpoint())->toBe('{}')
        ->and($projection->lockedUntil())->toBe('2024-01-01 00:00:00');
});

it('acquire lock for existing projection when now is greater than lock', function () {
    $this->clock->expects($this->once())->method('isGreaterThanNow')->willReturn(true);

    $createProjection = new CreateData('run');
    $this->provider->createProjection('stream-1', $createProjection);

    $updateProjection = new StartData('run', '2024-01-01 00:00:00');
    $this->provider->updateProjection('stream-1', $updateProjection);

    expect($this->provider->retrieve('stream-1')->lockedUntil())->toBe('2024-01-01 00:00:00');

    $acquireLock = new StartData('run', '2025-01-01 00:00:00');
    $this->provider->acquireLock('stream-1', $acquireLock);

    expect($this->provider->retrieve('stream-1')->lockedUntil())->toBe('2025-01-01 00:00:00');
});

it('raise projection already running exception when it can not acquired lock', function () {
    $this->clock->expects($this->once())->method('isGreaterThanNow')->willReturn(false);

    createProjection('stream-1', 'run')($this);

    $startProjection = new StartData('run', '2024-01-01 00:00:00');
    $this->provider->updateProjection('stream-1', $startProjection);

    expect($this->provider->retrieve('stream-1')->lockedUntil())->toBe('2024-01-01 00:00:00');

    $acquireLock = new StartData('run', '2025-01-01 00:00:00');
    $this->provider->acquireLock('stream-1', $acquireLock);
})->throws(ProjectionAlreadyRunning::class);

it('update projection', function (ProjectionData $data) {
    $this->clock->expects($this->never())->method('isGreaterThanNow');

    createProjection('stream-1', 'run')($this);

    $this->provider->updateProjection('stream-1', $data);

    $projection = $this->provider->retrieve('stream-1');
    $updated = $data->toArray();

    expect($projection->name())->toBe('stream-1')
        ->and($projection->status())->toBe($updated['status'] ?? 'run')
        ->and($projection->state())->toBe($updated['state'] ?? '{}')
        ->and($projection->checkpoint())->toBe($updated['checkpoint'] ?? '{}')
        ->and($projection->lockedUntil())->toBe($updated['locked_until'] ?? null);
})->with('projection data');

it('raise exception when updating projection with empty data', function () {
    createProjection('stream-1', 'run')($this);

    $data = new readonly class extends ProjectionData
    {
        public function toArray(): array
        {
            return [];
        }
    };

    $this->provider->updateProjection('stream-1', $data);
})->throws(InMemoryProjectionFailed::class, 'Provide at least one change to update projection stream-1');

it('raise projection not found exception when update projection for non existing projection', function (ProjectionData $data) {
    $this->provider->updateProjection('stream-1', $data);
})
    ->with('projection data')
    ->throws(ProjectionNotFound::class);

it('delete projection by name', function (array $streams) {
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
})->with(['streams' => [['stream-1', 'stream-2']]]);

it('raise exception when delete non existing projection', function () {
    expect($this->provider->exists('stream-1'))->toBeFalse();

    $this->provider->deleteProjection('stream-1');
})->throws(ProjectionNotFound::class);

it('filter projection by names', function (array $streams) {
    foreach ($streams as $stream) {
        createProjection($stream, 'run')($this);
    }

    expect($this->provider->filterByNames(...$streams))->toBe($streams)
        ->and($this->provider->filterByNames('foo', 'bar'))->toBe([]);

    if ($streams !== []) {
        expect($this->provider->filterByNames('stream-1', 'foo', 'bar'))->toBe(['stream-1'])
            ->and($this->provider->filterByNames('stream-2', 'foo', 'stream-1'))->toBe(['stream-2', 'stream-1']);
    }
})->with(['streams' => [[], ['stream-1', 'stream-2']]]);
