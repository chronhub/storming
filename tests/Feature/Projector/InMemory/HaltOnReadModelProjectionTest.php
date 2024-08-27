<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\HaltOn;

use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Storm\Projector\Support\StopWhen;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryProjectionExpectationTrait;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryReadModelProjectionTestBaseTrait;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

use function count;

uses(
    InMemoryReadModelProjectionTestBaseTrait::class,
    InMemoryProjectionExpectationTrait::class
);

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory;
    $this->readModel = new InMemoryReadModel;
});

test('stop projection when cycle reached', function (int $cycles) {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: 'balance',
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getIncrementUserStateReactor(), $this->getThenReactor())
        ->haltOn(StopWhen::cycleReached($cycles))
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    $this->assertProjectionReport(cycle: $cycles, ackedEvent: 4, totalEvent: 4);
})->with([[5], [10], [20]]);

test('stop projection with expiration', function () {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: 'balance',
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getIncrementUserStateReactor(), $this->getThenReactor())
        ->haltOn(StopWhen::timeExpired('seconds', 2))
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    $this->assertPartialProjectionReport(['acked_event' => 4, 'total_event' => 4]);
})->group('sleep');

test('stop projection when recoverable gap detected', function (array $retries) {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: 'balance',
        options: ['retries' => $retries],
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[3, 200]]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getIncrementUserStateReactor())
        ->haltOn(StopWhen::gapDetected(GapType::RECOVERABLE_GAP))
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    $this->assertPartialProjectionState('total', 100);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 1, totalEvent: 1);
})->with('projection options with non empty retries');

test('stop projection when unrecoverable gap detected', function (array $retries) {
    $this->setupProjection(
        [[$streamName = 'account', null]],
        projectionName: 'balance',
        options: ['retries' => $retries],
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [5, -150]]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getIncrementUserStateReactor(), $this->getThenReactor())
        ->haltOn(StopWhen::gapDetected(GapType::UNRECOVERABLE_GAP))
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    $this->assertPartialProjectionState('total', 300);
    $this->assertProjectionReport(cycle: count($retries), ackedEvent: 2, totalEvent: 2);
})->with('projection options with non empty retries');
