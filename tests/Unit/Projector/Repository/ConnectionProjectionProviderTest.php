<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Repository;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionModel;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Exception\ProjectionAlreadyExists;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Exception\ProjectionConnectionFailed;
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
use Storm\Projector\Storage\DatabaseProjectionProvider;
use Storm\Projector\Storage\Projection;

beforeEach(function () {
    $this->builder = mock(Builder::class);
    $this->connection = mock(Connection::class);
    $this->clock = mock(SystemClock::class);
});

dataset('update projection data', [
    'update status' => fn () => new UpdateStatusData('running'),
    'update lock' => fn () => new UpdateLockData('locked until'),
    'stop data' => fn () => new StopData('stopped', '{}', '{}', 'locked until'),
    'start again data' => fn () => new StartAgainData('running', 'locked until'),
    'release data' => fn () => new ReleaseData('running', null),
    'persist data' => fn () => new PersistData('running', '{"foo":"bar"}', 'locked until'),
    'reset data' => fn () => new ResetData('running', '{"foo":"bar"}', '{"bar":"foo"}'),
]);

test('create projection with default table name', function (): void {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder)
        ->twice();

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('exists')->andReturn(false);
    $this->builder->expects('insert')->with([
        'name' => $projectionName,
        'status' => 'running',
        'state' => '{}',
        'checkpoint' => '{}',
        'locked_until' => null,
    ])->andReturn(true);

    $data = new CreateData('running');

    $projectionProvider->createProjection($projectionName, $data);
});

test('create projection with custom table name', function (): void {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock, 'custom-table-name');
    $this->connection->expects('table')
        ->with('custom-table-name')
        ->andReturn($this->builder)
        ->twice();

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('exists')->andReturn(false);
    $this->builder->expects('insert')->with([
        'name' => $projectionName,
        'status' => 'running',
        'state' => '{}',
        'checkpoint' => '{}',
        'locked_until' => null,
    ])->andReturn(true);

    $data = new CreateData('running');
    $projectionProvider->createProjection($projectionName, $data);
});

test('raise projection already exists exception when create projection', function (): void {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder);

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('exists')->andReturn(true);
    $this->builder->expects('insert')->never();

    $data = new CreateData('running');
    $projectionProvider->createProjection($projectionName, $data);
})->throws(ProjectionAlreadyExists::class);

test('raise exception when projection data argument is not create data instance', function (): void {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')->never();

    $projectionName = 'projection-name';

    $this->builder->expects('where')->never();
    $this->builder->expects('exists')->never();
    $this->builder->expects('insert')->never();

    $data = new UpdateStatusData('running');

    $projectionProvider->createProjection($projectionName, $data);
})->throws(InvalidArgumentException::class, 'Invalid data provided, expected class '.CreateData::class);

test('raise exception when fails to create projection', function () {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder)
        ->twice();

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('exists')->andReturn(false);
    $this->builder->expects('insert')->with([
        'name' => $projectionName,
        'status' => 'running',
        'state' => '{}',
        'checkpoint' => '{}',
        'locked_until' => null,
    ])->andReturn(false);

    $data = new CreateData('running');

    $projectionProvider->createProjection($projectionName, $data);
})->throws(ProjectionConnectionFailed::class, 'Fail to create projection with name projection-name');

test('acquire lock', function (): void {
    $this->clock->expects('generate')->andReturn('point in time locked until');

    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder);

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('whereRaw')->with('locked_until IS NULL OR locked_until < ?', ['point in time locked until'])->andReturn($this->builder);
    $this->builder->expects('update')->with([
        'status' => 'running',
        'locked_until' => 'point in time locked until',
    ])->andReturn(1);

    $data = new StartData('running', 'point in time locked until');
    $projectionProvider->acquireLock($projectionName, $data);
});

test('raise exception when projection data not start data instance with acquire lock', function (): void {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')->never();

    $data = new UpdateStatusData('running');

    $projectionProvider->acquireLock('foo', $data);
})->throws(InvalidArgumentException::class, 'Invalid data provided, expected class '.StartData::class);

test('raise projection not found exception when acquire lock', function (): void {
    $this->clock->expects('generate')->andReturn('point in time locked until');

    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder)
        ->twice();

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('whereRaw')->with('locked_until IS NULL OR locked_until < ?', ['point in time locked until'])->andReturn($this->builder);
    $this->builder->expects('update')->with([
        'status' => 'running',
        'locked_until' => 'point in time locked until',
    ])->andReturn(0);

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('exists')->andReturn(false);

    $data = new StartData('running', 'point in time locked until');
    $projectionProvider->acquireLock($projectionName, $data);
})->throws(ProjectionNotFound::class, 'Projection projection-name not found');

test('raise projection already running exception when acquire lock fails', function (): void {
    $this->clock->expects('generate')->andReturn('point in time locked until');

    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder)
        ->twice();

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('whereRaw')->with('locked_until IS NULL OR locked_until < ?', ['point in time locked until'])->andReturn($this->builder);
    $this->builder->expects('update')->with([
        'status' => 'running',
        'locked_until' => 'point in time locked until',
    ])->andReturn(0);

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('exists')->andReturn(true);

    $data = new StartData('running', 'point in time locked until');
    $projectionProvider->acquireLock($projectionName, $data);
})->throws(ProjectionAlreadyRunning::class);

test('delete projection', function (): void {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder);

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('delete')->andReturn(1);

    $projectionProvider->deleteProjection($projectionName);
});

test('raise projection not found exception when delete projection', function (): void {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder)
        ->twice();

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('exists')->andReturn(false);

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('delete')->andReturn(0);

    $projectionProvider->deleteProjection($projectionName);
})->throws(ProjectionNotFound::class, 'Projection projection-name not found');

test('raise projection connection failed exception when delete projection fails', function (): void {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder)
        ->twice();

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('exists')->andReturn(true);

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('delete')->andReturn(0);

    $projectionProvider->deleteProjection($projectionName);
})->throws(ProjectionConnectionFailed::class, 'Failed to delete projection with name projection-name');

test('update projection', function (ProjectionData $updateData) {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder);

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('update')->with($updateData->toArray())->andReturn(1);

    $projectionProvider->updateProjection($projectionName, $updateData);
})->with('update projection data');

test('raise projection not found when update projection', function (ProjectionData $updateData) {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder)
        ->twice();

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('exists')->andReturn(false);

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('update')->with($updateData->toArray())->andReturn(0);

    $projectionProvider->updateProjection($projectionName, $updateData);
})
    ->with('update projection data')
    ->throws(ProjectionNotFound::class, 'Projection projection-name not found');

test('raise projection connection failed when update projection fails', function (ProjectionData $updateData) {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder)
        ->twice();

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('exists')->andReturn(true);

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('update')->with($updateData->toArray())->andReturn(0);

    $projectionProvider->updateProjection($projectionName, $updateData);
})->with([
    'update status' => fn () => new UpdateStatusData('running'),
    'update lock' => fn () => new UpdateLockData('locked until'),
    'stop data' => fn () => new StopData('stopped', '{}', '{}', 'locked until'),
    'start again data' => fn () => new StartAgainData('running', 'locked until'),
    'release data' => fn () => new ReleaseData('running', null),
    'persist data' => fn () => new PersistData('running', '{"foo":"bar"}', 'locked until'),
    'reset data' => fn () => new ResetData('running', '{"foo":"bar"}', '{"bar":"foo"}'),
])
    ->with('update projection data')
    ->throws(ProjectionConnectionFailed::class, 'Failed to update projection with name projection-name');

test('retrieve projection from array', function () {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder);

    $projectionName = 'projection-name';

    $model = [
        'name' => $projectionName,
        'status' => 'running',
        'state' => '{"foo":"bar"}',
        'checkpoint' => '{"bar":"foo"}',
        'locked_until' => 'point in time locked until',
    ];
    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('first')->andReturn($model);

    $projection = $projectionProvider->retrieve($projectionName);

    expect($projection)->toBeInstanceOf(ProjectionModel::class)
        ->and($projection)->toBeInstanceOf(Projection::class)
        ->and($projection->name())->toBe($projectionName)
        ->and($projection->status())->toBe('running')
        ->and($projection->state())->toBe('{"foo":"bar"}')
        ->and($projection->checkpoint())->toBe('{"bar":"foo"}')
        ->and($projection->lockedUntil())->toBe('point in time locked until');
});

test('retrieve projection from stdClass', function () {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder);

    $projectionName = 'projection-name';

    $model = [
        'name' => $projectionName,
        'status' => 'running',
        'state' => '{"foo":"bar"}',
        'checkpoint' => '{"bar":"foo"}',
        'locked_until' => 'point in time locked until',
    ];
    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('first')->andReturn((object) $model);

    $projection = $projectionProvider->retrieve($projectionName);

    expect($projection)->toBeInstanceOf(ProjectionModel::class)
        ->and($projection)->toBeInstanceOf(Projection::class)
        ->and($projection->name())->toBe($projectionName)
        ->and($projection->status())->toBe('running')
        ->and($projection->state())->toBe('{"foo":"bar"}')
        ->and($projection->checkpoint())->toBe('{"bar":"foo"}')
        ->and($projection->lockedUntil())->toBe('point in time locked until');
});

test('return null when projection not found', function () {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder);

    $projectionName = 'projection-name';

    $this->builder->expects('where')->with('name', $projectionName)->andReturn($this->builder);
    $this->builder->expects('first')->andReturn(null);

    $projection = $projectionProvider->retrieve($projectionName);

    expect($projection)->toBeNull();
});

test('filter by projection names', function (array $projectionNames, array $expected) {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder);

    $plucks = new Collection($expected);
    $this->builder->expects('whereIn')->with('name', $projectionNames)->andReturn($this->builder);
    $this->builder->expects('pluck')->with('name')->andReturn($plucks);

    $result = $projectionProvider->filterByNames(...$projectionNames);

    expect($result)->toBe($expected);
})->with([
    [[], []],
    [['foo'], ['foo']],
    [['foo', 'bar'], ['foo', 'bar']],
    [['foo', 'bar', 'baz'], ['foo', 'bar']],
]);

test('projection exists', function (bool $exists) {
    $projectionProvider = new DatabaseProjectionProvider($this->connection, $this->clock);
    $this->connection->expects('table')
        ->with($projectionProvider::TABLE_NAME)
        ->andReturn($this->builder);
    $this->builder->expects('where')->with('name', 'projection-name')->andReturn($this->builder);
    $this->builder->expects('exists')->andReturn($exists);

    expect($projectionProvider->exists('projection-name'))->toBe($exists);
})->with('projection exists');
