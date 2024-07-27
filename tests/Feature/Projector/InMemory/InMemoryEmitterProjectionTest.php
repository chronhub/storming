<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Storm\Projector\ProjectionStatus;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryEmitterProjectionTestBaseTrait;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryProjectionExpectationTrait;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

use function count;

uses(
    InMemoryEmitterProjectionTestBaseTrait::class,
    InMemoryProjectionExpectationTrait::class
);

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory();
});

dataset('event stream name', ['balance1', 'balance2']);
dataset('should record gaps', [[true], [false]]);

test('emit stream event to event store under the projection name', function (string $eventStream) {
    $this->setupProjection(
        streamName: $eventStream,
        projectionName: $projectionName = 'operation'
    );

    $this->assertProjectionExists($projectionName, false);
    $this->assertStreamExists($eventStream, false);

    $this->balanceEventStore($eventStream)
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->assertStreamExists($eventStream, true);
    $this->assertStreamExists($projectionName, false);

    $reactors = $this->getEmitterReactor();

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($eventStream)
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->run(false);

    $this->assertProjectionExists($projectionName, true);
    $this->assertStreamExists($projectionName, true);

    $this->assertProjectionState(['total' => 100]);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $eventStream, position: 4);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);
})->with('event stream name');

test('emit stream event with retries and gaps', function (array $retries, bool $recordGap) {
    $options = ['retries' => $retries, 'recordGap' => $recordGap];

    $this->setupProjection(
        streamName: $eventStream = 'balance',
        projectionName: $projectionName = 'operation',
        options: $options
    );

    $this->assertProjectionExists($projectionName, false);
    $this->assertStreamExists($eventStream, false);

    $this->balanceEventStore($eventStream)
        ->withBalanceCreated(1, 200)
        ->withVersioningAmount([[4, 200], [7, -150], [10, -70]]);

    $this->assertStreamExists($eventStream, true);
    $this->assertStreamExists($projectionName, false);

    $reactors = $this->getEmitterReactor(keepRunning: true, stopAt: ['total', 180]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($eventStream)
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->run(true);

    $this->assertProjectionExists($projectionName, true);
    $this->assertStreamExists($projectionName, true);
    $this->assertProjectionState(['total' => 180]);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);

    $expectedGaps = $recordGap ? [2, 3, 5, 6, 8, 9] : [];
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $eventStream, position: 10, gaps: $expectedGaps);

    $expectedCycles = $this->calculateExpectedCycles(
        numberOfEventWithNoGap: 1,
        numberOfRetry: count($retries),
        numberOfEventWithGap: 3
    );
    $this->assertProjectionReport(cycle: $expectedCycles, ackedEvent: 4, totalEvent: 4);
})->with('projection options with non empty retries', 'should record gaps');

test('link event to a new stream', function () {
    $emittedStream = 'operation_emitted';

    $this->setupProjection(
        streamName: $eventStream = 'balance',
        projectionName: $projectionName = 'operation'
    );

    $this->assertProjectionExists($projectionName, false);
    $this->assertStreamExists($eventStream, false);

    $this->balanceEventStore($eventStream)
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -149]]);

    $this->assertStreamExists($eventStream, true);
    $this->assertStreamExists($emittedStream, false);

    $reactors = $this->getEmitterReactor(linkTo: $emittedStream);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($eventStream)
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->run(false);

    $this->assertStreamExists($emittedStream, true);
    $this->assertProjectionExists($projectionName, true);

    $this->assertProjectionState(['total' => 1]);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $eventStream, position: 4);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);
});

test('link event to a new stream with gaps', function (array $retries, bool $recordGap) {
    $emittedStream = 'operation_emitted';

    $this->setupProjection(
        streamName: $eventStream = 'balance',
        projectionName: $projectionName = 'operation',
        options: ['retries' => $retries, 'recordGap' => $recordGap]
    );

    $this->assertProjectionExists($projectionName, false);
    $this->assertStreamExists($eventStream, false);

    $this->balanceEventStore($eventStream)
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[4, 200], [7, -150], [10, -149]]);

    $this->assertStreamExists($eventStream, true);
    $this->assertStreamExists($emittedStream, false);

    $reactors = $this->getEmitterReactor(linkTo: $emittedStream, keepRunning: true, stopAt: ['total', 1]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($eventStream)
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->run(true);

    $this->assertStreamExists($emittedStream, true);
    $this->assertProjectionExists($projectionName, true);

    $this->assertProjectionState(['total' => 1]);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);

    $expectedGaps = $recordGap ? [2, 3, 5, 6, 8, 9] : [];
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $eventStream, position: 10, gaps: $expectedGaps);

    $expectedCycles = $this->calculateExpectedCycles(
        numberOfEventWithNoGap: 1,
        numberOfRetry: count($retries),
        numberOfEventWithGap: 3
    );
    $this->assertProjectionReport(cycle: $expectedCycles, ackedEvent: 4, totalEvent: 4);
})->with(
    'projection options with non empty retries',
    'projection options record gap'
);

/**
 * The purpose of the internal position is to track the original position of the original stream event
 * when the event was emitted or linked to.
 *
 * The internal position header of an event only exists
 * when the stream event has been deserialized from the event store.
 * Depends on the strategy used, the position is the aggregate version or the incremental sequence number,
 * when the event has been stored.
 *
 * @todo in integration test
 * If the event has already been stored with an internal position header, emitted or linked to,
 * it would just return the same internal position, mo matter of position in the event store.
 */
test('internal position header of emitted event is position of original stream event', function () {
    $this->setupProjection(
        streamName: $eventStream = 'balance',
        projectionName: $projectionName = 'operation',
        balanceId: $balanceId = BalanceId::create()
    );

    $this->assertProjectionExists($projectionName, false);
    $this->assertStreamExists($eventStream, false);

    $this->balanceEventStore($eventStream)
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -149]]);

    $this->assertStreamExists($eventStream, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertInternalPositionsOfStream($eventStream, $balanceId, [1, 2, 3, 4]);

    $reactors = $this->getEmitterReactor();

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($eventStream)
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->run(false);

    $this->assertProjectionExists($projectionName, true);
    $this->assertStreamExists($projectionName, true);

    $this->assertProjectionState(['total' => 1]);
    $this->assertInternalPositionsOfStream($projectionName, $balanceId, [1, 2, 3, 4]);
});

test('internal position header of link_to event is position of original stream event', function (array $retries) {
    $emittedStream = 'operation_emitted';

    $this->setupProjection(
        streamName: $eventStream = 'balance',
        projectionName: $projectionName = 'operation',
        options: ['retries' => $retries],
        balanceId: $balanceId = BalanceId::create()
    );

    $this->assertStreamExists($eventStream, false);
    $this->assertStreamExists($emittedStream, false);

    $this->balanceEventStore($eventStream)
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[5, 200], [20, -150], [100, -149]]);

    $this->assertStreamExists($eventStream, true);
    $this->assertInternalPositionsOfStream($eventStream, $balanceId, [1, 5, 20, 100]);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($emittedStream, false);
    $this->assertProjectionExists($projectionName, false);

    $reactors = $this->getEmitterReactor(linkTo: $emittedStream, keepRunning: true, stopAt: ['total', 1]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($eventStream)
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->run(true);

    $this->assertProjectionExists($projectionName, true);
    $this->assertStreamExists($emittedStream, true);
    $this->assertStreamExists($projectionName, false);

    $this->assertProjectionState(['total' => 1]);
    $this->assertInternalPositionsOfStream($emittedStream, $balanceId, [1, 5, 20, 100]);
})->with('projection options with non empty retries');

test('emit with many streams', function () {})->todo();

test('link with many streams', function () {})->todo();
