<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Operations;

use Storm\Projector\ProjectionStatus;
use Storm\Projector\Scope\ReadModelScope;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryProjectionExpectationTrait;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryReadModelProjectionTestBaseTrait;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

use function count;

uses(
    InMemoryReadModelProjectionTestBaseTrait::class,
    InMemoryProjectionExpectationTrait::class
);

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory();
    $this->readModel = new InMemoryReadModel();
});

test('resets the read model projection', function () {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getReadModelReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    $this->assertPartialProjectionState('total', 100);
    $this->assertReadModelBalance($streamName, 100);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 4);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);

    // reset read model and reset user state
    $this->projector->reset();

    expect($this->readModel->getContainer())->toBeEmpty();
    $this->assertProjectionExists($projectionName, true);
    $this->assertProjectionState(['total' => 0]);

    // run again
    $this->projector->run(false);

    $this->assertReadModelBalance(streamName: $streamName, total: 100);
    $this->assertProjectionExists($projectionName, true);
    $this->assertPartialProjectionState('total', 100);
});

test('resets from monitoring within the projection instance', function () {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $monitor = $this->factory->getMonitor();
    $resetStatus = null;

    /**
     * @param-closure-this ReadModelScope $reactors
     */
    $reactors = [
        function (BalanceCreated $event) {
            $this->userState->set('total', $event->amount());
            $this->readModel()->insert($event->id(), ['total' => $event->amount()]);
        },
        function (BalanceAdded $event) {
            $this->userState->increment('total', $event->amount());
            $this->readModel()->increment($event->id(), 'total', $event->amount());
        },
        function (BalanceSubtracted $event) {
            $this->userState->decrement('total', $event->amount());
            $this->readModel()->decrement($event->id(), 'total', $event->amount());
        },
    ];

    $thenReactor = function (ReadModelScope $scope) use ($monitor, &$resetStatus) {
        $scope->userState()->push('events', [$scope->event()::class]);

        if ($resetStatus === null && count($scope->userState()['events']) === 4) {
            $monitor->markAsReset('balance');
            $resetStatus = $monitor->statusOf('balance');
        }
    };

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors, $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    expect($resetStatus)->toBe(ProjectionStatus::RESETTING->value);
    $this->assertReadModelDown();
    $this->assertProjectionExists($projectionName, true);
    $this->assertProjectionState(['total' => 0]);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);

    // todo assert report time
    // sleep(1);

    $this->projector->run(false);

    $this->assertReadModelBalance($streamName, 100);
    $this->assertProjectionExists($projectionName, true);
    $this->assertPartialProjectionState('total', 100);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);
});
