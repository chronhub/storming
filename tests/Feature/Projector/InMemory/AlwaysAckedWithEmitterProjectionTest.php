<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory;

use Storm\Projector\Scope\EmitterScope;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryEmitterProjectionTestBaseTrait;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryProjectionExpectationTrait;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

uses(
    InMemoryEmitterProjectionTestBaseTrait::class,
    InMemoryProjectionExpectationTrait::class
);

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory();
});

test('should emit all stream events to event store under the projection name', function () {
    $this->setupProjection(
        [[$stream1 = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->assertProjectionExists($projectionName, false);
    $this->assertStreamExists($stream1, false);

    $this->balanceEventStore($stream1)
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->assertStreamExists($stream1, true);
    $this->assertStreamExists($projectionName, false);

    $reactors = [];
    $thenReactor = function (EmitterScope $scope): void {
        $scope->userState()->push('events', $scope->event()::class);
        $scope->emit($scope->event());
    };

    $this->projector
        ->initialize(fn (): array => ['events' => []])
        ->subscribeToStream($stream1)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(false);

    $this->assertProjectionExists($projectionName, true);
    $this->assertStreamExists($projectionName, true);

    $this->assertPartialProjectionState('events',
        [
            BalanceCreated::class,
            BalanceAdded::class,
            BalanceSubtracted::class,
            BalanceSubtracted::class,
        ]);

    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);
});

test('should link to all stream events to event store under a stream name', function () {
    $this->setupProjection(
        [[$stream1 = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->assertProjectionExists($projectionName, false);
    $this->assertStreamExists($stream1, false);

    $this->balanceEventStore($stream1)
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->assertStreamExists($stream1, true);
    $this->assertStreamExists($projectionName, false);

    $reactors = [];
    $thenReactor = function (EmitterScope $scope): void {
        $scope->linkTo('balanceOne', $scope->event());
    };

    $this->projector
        ->initialize(fn (): array => ['events' => []])
        ->subscribeToStream($stream1)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(false);

    $this->assertProjectionExists($projectionName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists('balanceOne', true);

    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);
});

test('test emit internal stream to an incremental event store', function () {
    $this->setupProjection(
        [[$stream1 = 'account1', null], [$stream2 = 'account2', null]],
        projectionName: $projectionName = '$all',
        connection: 'in_memory-incremental',
    );

    $this->assertProjectionExists($projectionName, false);
    $this->assertStreamExists($stream1, false);
    $this->assertStreamExists($stream2, false);

    $this->balanceEventStore($stream1)->make(50);
    $this->balanceEventStore($stream2)->make(100);

    $this->assertStreamExists($stream1, true);
    $this->assertStreamExists($stream2, true);
    $this->assertStreamExists($projectionName, false);

    $reactors = [];
    $thenReactor = function (EmitterScope $scope): void {
        $scope->userState()->increment($scope->streamName());
        $scope->emit($scope->event());
    };

    $this->projector
        ->initialize(fn (): array => [$stream1 => 0, $stream2 => 0])
        ->subscribeToAll()
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(false);

    $this->assertProjectionState([$stream1 => 50, $stream2 => 100]);
    $this->assertProjectionExists($projectionName, true);
    $this->assertStreamExists($projectionName, true);

    $this->assertProjectionReport(cycle: 1, ackedEvent: 150, totalEvent: 150);
});
