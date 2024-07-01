<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Storm\Projector\ProjectionStatus;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\BalanceEventStore;
use Storm\Tests\Domain\ProjectionBalanceReactor;
use Storm\Tests\Feature\InMemoryTestingFactory;

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory();
});

dataset('event store stream name', [
    'balance',
    'another_balance',
]);

test('emit event', function (string $eventStoreStreamName) {
    $manager = $this->factory->createProjectorManager();

    $this->factory
        ->assertProjectionExists('operation', false)
        ->assertStreamExists('operation', false);

    $balanceId = BalanceId::create();
    $streamName = new StreamName($eventStoreStreamName);
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 200)
        ->withBalanceSubtracted(3, 150)
        ->withBalanceSubtracted(4, 50);

    $projector = $manager->newEmitterProjector('operation');
    $reactors = ProjectionBalanceReactor::getEmitReactor(null);

    $projector
        ->initialize(fn (): array => ['balance' => 0])
        ->subscribeToStream($eventStoreStreamName)
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    $this->factory
        ->assertProjectionExists('operation', true)
        ->assertStreamExists('operation', true)
        ->assertProjectionModel(
            streamName: 'operation',
            state: $this->factory->serializer->encode($projector->getState(), 'json'),
            status: ProjectionStatus::IDLE->value,
            lockedUntil: null
        )
        ->assertEmittedProjectionModelCheckpoint(
            streamName: 'operation',
            fromStreamName: $eventStoreStreamName,
            position: 4,
            gaps: [],
            gapType: null
        );
})->with('event store stream name');

test('link event to a new stream', function (string $eventStoreStreamName) {
    $manager = $this->factory->createProjectorManager();

    $this->factory
        ->assertProjectionExists('operation', false)
        ->assertStreamExists('operation', false)
        ->assertStreamExists('new_stream', false);

    $balanceId = BalanceId::create();
    $streamName = new StreamName($eventStoreStreamName);
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 200)
        ->withBalanceSubtracted(3, 150)
        ->withBalanceSubtracted(4, 50);

    $projector = $manager->newEmitterProjector('operation');
    $reactors = ProjectionBalanceReactor::getEmitReactor('new_stream');

    $projector
        ->initialize(fn (): array => ['balance' => 0])
        ->subscribeToStream($eventStoreStreamName)
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    $this->factory
        ->assertProjectionExists('operation', true)
        ->assertStreamExists('operation', false)
        ->assertStreamExists('new_stream', true)
        ->assertProjectionModel(
            streamName: 'operation',
            state: $this->factory->serializer->encode($projector->getState(), 'json'),
            status: ProjectionStatus::IDLE->value,
            lockedUntil: null
        )
        ->assertEmittedProjectionModelCheckpoint(
            streamName: 'operation',
            fromStreamName: $eventStoreStreamName,
            position: 4,
            gaps: [],
            gapType: null
        );
})->with('event store stream name');

test('emit with many streams', function () {})->todo();

test('link with many streams', function () {})->todo();
