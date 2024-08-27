<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Storm\Clock\ClockFactory;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Projector\Exception\InvalidArgumentException;
use Storm\Projector\Exception\ProjectionAlreadyExists;
use Storm\Projector\Exception\ProjectionAlreadyRunning;
use Storm\Projector\Exception\ProjectionNotFound;
use Storm\Projector\Repository\Data\CreateData;
use Storm\Projector\Repository\Data\ProjectionData;
use Storm\Projector\Repository\Data\StartData;
use Storm\Projector\Repository\Data\UpdateLockData;
use Storm\Projector\Repository\DatabaseProjectionProvider;

uses(RefreshDatabase::class);

beforeEach(function () {
    $connection = $this->app['db']->connection();

    $connection->getSchemaBuilder()->create(
        DatabaseProjectionProvider::TABLE_NAME, function (Blueprint $table) {
            $table->string('name')->primary();
            $table->string('status');
            $table->string('state');
            $table->string('checkpoint');
            $table->timestamp('locked_until')->nullable();
        });

    $this->projectionProvider = new DatabaseProjectionProvider(
        $connection,
        ClockFactory::create()
    );
});

function createConnectionProjection(ProjectionProvider $projectionProvider, string $name, string $status): void
{
    expect($projectionProvider->exists($name))->toBeFalse();

    $data = new CreateData($status);
    $projectionProvider->createProjection($name, $data);

    expect($projectionProvider->exists($name))->toBeTrue();
}

function updateLockedUntil(ProjectionProvider $projectionProvider, string $name, string $lockedUntil): void
{
    expect($projectionProvider->exists($name))->toBeTrue();

    $projectionProvider->updateProjection($name, new UpdateLockData($lockedUntil));
}

test('create projection', function () {
    createConnectionProjection($this->projectionProvider, 'test', 'running');

    $projection = $this->projectionProvider->retrieve('test');

    expect($projection->name())->toBe('test')
        ->and($projection->status())->toBe('running')
        ->and($projection->state())->toBe('{}')
        ->and($projection->checkpoint())->toBe('{}')
        ->and($projection->lockedUntil())->toBeNull();
});

describe('raise exception on create projection', function () {
    test('with invalid data instance', function () {
        $data = new readonly class extends ProjectionData
        {
            public function toArray(): array
            {
                return [];
            }
        };

        $this->projectionProvider->createProjection('test', $data);
    })->throws(InvalidArgumentException::class, 'Invalid data provided, expected class '.CreateData::class);

    test('when projection already exists', function () {
        createConnectionProjection($this->projectionProvider, 'test', 'running');

        $data = new CreateData('running');
        $this->projectionProvider->createProjection('test', $data);
    })->throws(ProjectionAlreadyExists::class);
});

test('acquire lock when locked until is null', function () {
    createConnectionProjection($this->projectionProvider, 'test', 'running');

    $data = new StartData('running', '2025-01-01T00:00:00.000000');
    $this->projectionProvider->acquireLock('test', $data);

    $projection = $this->projectionProvider->retrieve('test');

    expect($projection->lockedUntil())->toBe('2025-01-01T00:00:00.000000');
});

test('acquire lock when locked until is in the past', function () {
    createConnectionProjection($this->projectionProvider, 'test', 'running');
    updateLockedUntil($this->projectionProvider, 'test', '2024-02-01T00:00:00.000000');

    $data = new StartData('running', '2024-04-01T00:00:00.000000');
    $this->projectionProvider->acquireLock('test', $data);

    $projection = $this->projectionProvider->retrieve('test');
    expect($projection->lockedUntil())->toBe('2024-04-01T00:00:00.000000');
});

test('raise projection already running when locked until is in the future', function () {
    createConnectionProjection($this->projectionProvider, 'test', 'running');
    updateLockedUntil($this->projectionProvider, 'test', '2025-02-01T00:00:00.000000');

    $data = new StartData('running', '2024-01-01T00:00:00.000000');

    $this->projectionProvider->acquireLock('test', $data);
})->throws(ProjectionAlreadyRunning::class);

test('raise projection not found when acquire lock on non existing projection', function () {
    $data = new StartData('running', '2025-01-01T00:00:00.000000');

    $this->projectionProvider->acquireLock('test', $data);
})->throws(ProjectionNotFound::class, 'Projection test not found');

test('delete projection', function () {
    createConnectionProjection($this->projectionProvider, 'test', 'running');

    $this->projectionProvider->deleteProjection('test');

    expect($this->projectionProvider->exists('test'))->toBeFalse();
});

test('raise projection not found when delete non existing projection', function () {
    $this->projectionProvider->deleteProjection('test');
})->throws(ProjectionNotFound::class, 'Projection test not found');

test('filter by names', function () {
    createConnectionProjection($this->projectionProvider, 'test', 'running');
    createConnectionProjection($this->projectionProvider, 'test2', 'running');
    createConnectionProjection($this->projectionProvider, 'test3', 'running');

    expect($this->projectionProvider->filterByNames('test', 'test2', 'test3'))->toBe(['test', 'test2', 'test3'])
        ->and($this->projectionProvider->filterByNames('test2', 'test3'))->toBe(['test2', 'test3'])
        ->and($this->projectionProvider->filterByNames('foo', 'test3', 'bar'))->toBe(['test3'])
        ->and($this->projectionProvider->filterByNames('foo', 'bar'))->toBe([])
        ->and($this->projectionProvider->filterByNames())->toBe([]);
});

test('incomplete test', function () {})->todo();
