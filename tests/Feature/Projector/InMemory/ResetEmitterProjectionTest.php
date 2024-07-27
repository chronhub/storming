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

test('resets the projection and deletes the emitted events in the event store', function () {
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

    // reset
    $this->projector->reset();

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertPartialProjectionState('total', 0);

    // run again
    $this->projector->run(false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, true);
    $this->assertPartialProjectionState('total', 100);
});

/**
 * Emitted event under another stream must be deleted manually.
 */
test('resets the projection and does not deletes the link to emitted events in the event store', function () {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $emittedStream = 'account_balance_total';
    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($emittedStream, false);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getEmitterReactor($emittedStream))
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(inBackground: false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($emittedStream, true);

    // reset
    $this->projector->reset();

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($emittedStream, true);
    $this->assertPartialProjectionState('total', 0);

    // checkMe Running again only works in-memory
    //  From a connection perspective, it should fail as event already exists
    $this->projector->run(false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($emittedStream, true);
    $this->assertPartialProjectionState('total', 100);

});
