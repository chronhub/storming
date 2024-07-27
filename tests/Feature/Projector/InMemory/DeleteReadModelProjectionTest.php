<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Operations;

use Storm\Projector\ProjectionStatus;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryProjectionExpectationTrait;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryReadModelProjectionTestBaseTrait;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

uses(
    InMemoryReadModelProjectionTestBaseTrait::class,
    InMemoryProjectionExpectationTrait::class
);

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory();
    $this->readModel = new InMemoryReadModel();

    $this->assertReadModelBalance = function (int $total): void {
        expect($this->readModel->getContainer())->toBe(
            [$this->balanceId->toString() => ['total' => $total]]
        );
    };
});

test('deletes the projection and keeps the read model', function () {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getReadModelReactor())
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(inBackground: false);

    $this->assertPartialProjectionState('total', 100);
    ($this->assertReadModelBalance)(100);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 4);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);

    // delete the projection and reset user state
    $this->projector->delete(false);

    ($this->assertReadModelBalance)(100);
    $this->assertProjectionExists($projectionName, false);
    $this->assertProjectionState(['total' => 0]);

    // run again
    $this->projector->run(false);

    ($this->assertReadModelBalance)(100);
    $this->assertProjectionExists($projectionName, true);
    $this->assertPartialProjectionState('total', 100);
});

test('deletes the projection and the read model', function () {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getReadModelReactor())
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(inBackground: false);

    $this->assertPartialProjectionState('total', 100);
    ($this->assertReadModelBalance)(100);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 4);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);

    $this->projector->delete(true);

    expect($this->readModel->getContainer())->toBeEmpty();
    $this->assertProjectionExists($projectionName, false);
    $this->assertProjectionState(['total' => 0]);

    // run again
    $this->projector->run(false);

    ($this->assertReadModelBalance)(100);
    $this->assertProjectionExists($projectionName, true);
    $this->assertPartialProjectionState('total', 100);
});
