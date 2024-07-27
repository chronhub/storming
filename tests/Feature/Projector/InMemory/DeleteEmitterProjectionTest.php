<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Operations;

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

test('deletes the projection and keeps the emitted events in the event store', function () {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getEmitterReactor())
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(inBackground: false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, true);

    // delete the projection and reset user state
    $this->projector->delete(false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, true);
    $this->assertPartialProjectionState('total', 0);

    // run again
    $this->projector->run(false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, true);
    $this->assertPartialProjectionState('total', 100);
});

test('deletes the projection and deletes the emitted events in the event store', function () {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getEmitterReactor())
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(inBackground: false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, true);

    // delete the projection and reset user state
    $this->projector->delete(true);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertPartialProjectionState('total', 0);

    // run again
    $this->projector->run(false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, true);
    $this->assertPartialProjectionState('total', 100);
});

test('deletes the projection and keeps the link to events in the event store', function () {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
    );

    $linkTo = 'account_balance_total';

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($linkTo, false);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getEmitterReactor($linkTo))
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(inBackground: false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($linkTo, true);

    // delete the projection and reset user state
    $this->projector->delete(false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($linkTo, true);
    $this->assertPartialProjectionState('total', 0);

    // run again
    $this->projector->run(false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($linkTo, true);

    $this->assertPartialProjectionState('total', 100);
});

/**
 * Emitted event under another stream must be deleted manually.
 */
test('deletes the projection and does not deletes the link to events in the event store', function () {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
    );

    $linkTo = 'account_balance_total';

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($linkTo, false);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getEmitterReactor($linkTo))
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(inBackground: false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($linkTo, true);

    // delete
    $this->projector->delete(true);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($linkTo, true);
    $this->assertPartialProjectionState('total', 0);

    // run again
    $this->projector->run(false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($linkTo, true);

    $this->assertPartialProjectionState('total', 100);
});
