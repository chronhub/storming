<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use Storm\Clock\PointInTime;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Projector\Exception\InMemoryProjectionFailed;
use Storm\Projector\Exception\ProjectionAlreadyExists;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\Storage\Data\CreateData;
use Storm\Projector\Storage\Data\PersistData;
use Storm\Projector\Storage\Data\ProjectionData;
use Storm\Projector\Storage\Data\ReleaseData;
use Storm\Projector\Storage\Data\ResetData;
use Storm\Projector\Storage\Data\StartAgainData;
use Storm\Projector\Storage\Data\StartData;
use Storm\Projector\Storage\Data\StopData;
use Storm\Projector\Storage\Data\UpdateLockData;
use Storm\Projector\Storage\Data\UpdateStatusData;
use Storm\Projector\Storage\InMemoryProjectionProvider;
use Storm\Tests\Testing\ProjectionProviderTestingTrait;

uses(ProjectionProviderTestingTrait::class);

beforeEach(function () {
    $this->clock = mock(SystemClock::class);
    $this->projectionProvider = new InMemoryProjectionProvider($this->clock);
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

dataset('filter by names with expectations', [
    [['stream2', 'stream1'],  ['stream2', 'stream1']],
    [['stream1', 'stream2'],  ['stream1', 'stream2']],
    [['stream1'], ['stream1']],
    [['stream1', 'foo'], ['stream1']],
    [['stream2', 'foo', 'stream1'], ['stream2', 'stream1']],
    [['stream1', 'foo', 'bar', 'stream2'], ['stream1', 'stream2']],
    [['foo'], []],
    [[], []],
]);

test('default instance', function () {
    expect($this->projectionProvider)->toBeInstanceOf(ProjectionProvider::class)
        ->and($this->projectionProvider->exists('stream1'))->toBeFalse()
        ->and($this->projectionProvider->retrieve('stream1'))->toBeNull()
        ->and($this->projectionProvider->filterByNames('stream1'))->toBe([]);
});

test('create projection', function () {
    $this->createProjection('stream1', 'run');

    $projection = $this->projectionProvider->retrieve('stream1');

    expect($projection->name())->toBe('stream1')
        ->and($projection->status())->toBe('run')
        ->and($projection->state())->toBe('{}')
        ->and($projection->checkpoint())->toBe('{}')
        ->and($projection->lockedUntil())->toBeNull();
});

test('acquire lock for existing projection with null lock', function () {
    $this->clock->shouldNotReceive('now');

    $this->createProjection('stream1', 'run');
    expect($this->projectionProvider->retrieve('stream1')->lockedUntil())->toBeNull();

    $this->projectionProvider->acquireLock('stream1', new UpdateLockData('2024-01-01 00:00:00'));

    $projection = $this->projectionProvider->retrieve('stream1');

    expect($projection->name())->toBe('stream1')
        ->and($projection->status())->toBe('run')
        ->and($projection->state())->toBe('{}')
        ->and($projection->checkpoint())->toBe('{}')
        ->and($projection->lockedUntil())->toBe('2024-01-01 00:00:00');
});

test('acquire lock for existing projection when now is greater than lock', function () {
    $now = PointInTime::fromString('2024-07-06T14:42:29.000000');
    $lockedUntil = '2024-07-06T14:42:28.000000';
    $newLockUntil = '2024-07-06T14:42:30.000000';
    $this->clock->expects('now')->andReturn($now);

    $this->createProjection('stream1', 'run');

    $this->projectionProvider->updateProjection('stream1', new StartData('run', $lockedUntil));
    expect($this->projectionProvider->retrieve('stream1')->lockedUntil())->toBe($lockedUntil);

    $this->projectionProvider->acquireLock('stream1', new StartData('run', $newLockUntil));
    expect($this->projectionProvider->retrieve('stream1')->lockedUntil())->toBe($newLockUntil);
});

test('update projection', function (ProjectionData $data) {
    $this->clock->expects('now')->never();

    $this->createProjection('stream1', 'run');

    $this->projectionProvider->updateProjection('stream1', $data);

    $projection = $this->projectionProvider->retrieve('stream1');
    $updated = $data->toArray();

    expect($projection->name())->toBe('stream1')
        ->and($projection->status())->toBe($updated['status'] ?? 'run')
        ->and($projection->state())->toBe($updated['state'] ?? '{}')
        ->and($projection->checkpoint())->toBe($updated['checkpoint'] ?? '{}')
        ->and($projection->lockedUntil())->toBe($updated['locked_until'] ?? null);
})->with('projection data');

test('delete projection by name', function (array $streams) {
    foreach ($streams as $stream) {
        $this->createProjection($stream, 'run');
    }

    expect($this->projectionProvider->exists($streams[0]))->toBeTrue()
        ->and($this->projectionProvider->exists($streams[1]))->toBeTrue();

    $this->projectionProvider->deleteProjection($streams[0]);

    expect($this->projectionProvider->exists($streams[0]))->toBeFalse()
        ->and($this->projectionProvider->exists($streams[1]))->toBeTrue();

    $this->projectionProvider->deleteProjection($streams[1]);

    expect($this->projectionProvider->exists($streams[0]))->toBeFalse()
        ->and($this->projectionProvider->exists($streams[1]))->toBeFalse();
})->with(['streams' => [['stream1', 'stream2']]]);

test('filter projection by names', function (array $filter, array $expectedStreams) {
    foreach ($expectedStreams as $stream) {
        $this->createProjection($stream, 'run');
    }

    expect($this->projectionProvider->filterByNames(...$filter))->toBe($expectedStreams);
})->with('filter by names with expectations');

describe('raise exception', function () {
    test('creating already existent projection with projection already exists exception', function () {
        $this->createProjection('stream1', 'idle');

        $this->projectionProvider->createProjection('stream1', new CreateData('run'));
    })->throws(ProjectionAlreadyExists::class);

    test('acquiring lock fails with projection already running exception', function () {
        $now = PointInTime::fromString('2024-07-06T14:42:28.000000');
        $lockedUntil = '2024-07-06T14:42:29.000000';

        $this->clock->expects('now')->andReturn($now);

        $this->createProjection('stream1', 'run');

        $startProjection = new StartData('run', $lockedUntil);
        $this->projectionProvider->updateProjection('stream1', $startProjection);

        expect($this->projectionProvider->retrieve('stream1')->lockedUntil())->toBe($lockedUntil);

        $acquireLock = new StartData('run', '2024-07-06T14:42:30.000000');
        $this->projectionProvider->acquireLock('stream1', $acquireLock);
    })->throws(ProjectionAlreadyRunning::class);

    test('deleting non existing projection with projection not found exception', function () {
        expect($this->projectionProvider->exists('stream1'))->toBeFalse();

        $this->projectionProvider->deleteProjection('stream1');
    })->throws(ProjectionNotFound::class);

    test('updating projection with empty data with in memory projection failed exception', function () {
        $this->createProjection('stream1', 'run');

        $data = new readonly class extends ProjectionData
        {
            public function toArray(): array
            {
                return [];
            }
        };

        $this->projectionProvider->updateProjection('stream1', $data);
    })->throws(InMemoryProjectionFailed::class, 'Provide at least one change to update projection stream1');

    test('updating no existent projection with projection not found exception', function (ProjectionData $data) {
        expect($this->projectionProvider->exists('stream1'))->toBeFalse();

        $this->projectionProvider->updateProjection('stream1', $data);
    })
        ->with('projection data')
        ->throws(ProjectionNotFound::class);
});
