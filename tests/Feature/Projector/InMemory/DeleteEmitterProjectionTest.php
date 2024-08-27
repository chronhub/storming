<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Operations;

use Storm\Chronicler\Exceptions\ConcurrencyException;
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

/**
 * Emitted event must be deleted manually.
 */
test('deletes the projection and keeps the emitted events in the event store', function () {
    $this->setupProjection(
        [[$streamName = 'account', null]],
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
        ->when($this->getEmitterReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, true);

    // delete without emitted events
    $this->projector->delete(false);

    $this->assertProjectionExists($projectionName, false);
    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, true);
    $this->assertProjectionState(['total' => 0]);

    // run again will fail
    $exception = null;

    try {
        $this->projector->run(false);
    } catch (ConcurrencyException $e) {
        $exception = $e;
    }

    expect($exception)->toBeInstanceOf(ConcurrencyException::class)
        ->and($exception->getMessage())->toBe("In memory concurrency detected for stream $projectionName");
});

test('deletes the projection and deletes the emitted events in the event store', function () {
    $this->setupProjection(
        [[$streamName = 'account', null]],
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
        ->when($this->getEmitterReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, true);

    // delete with emitted events
    $this->projector->delete(true);

    $this->assertProjectionExists($projectionName, false);
    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertProjectionState(['total' => 0]);

    // run again
    $this->projector->run(false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, true);
    $this->assertPartialProjectionState('total', 100);
});

/**
 * Link to event under another stream must be deleted manually.
 */
test('deletes the projection with or without emitted event with link to a new stream keeps the emitted events in the event store', function (bool $withEmittedEvent) {
    $this->setupProjection(
        [[$streamName = 'account', null]],
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
        ->when($this->getEmitterReactor(), $this->getThenReactor($linkTo))
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: false);

    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($linkTo, true);

    // delete the projection and reset user state
    $this->projector->delete($withEmittedEvent);

    $this->assertProjectionExists($projectionName, false);
    $this->assertStreamExists($streamName, true);
    $this->assertStreamExists($projectionName, false);
    $this->assertStreamExists($linkTo, true);
    $this->assertProjectionState(['total' => 0]);

    // run again will fail
    $exception = null;

    try {
        $this->projector->run(false);
    } catch (ConcurrencyException $e) {
        $exception = $e;
    }

    expect($exception)->toBeInstanceOf(ConcurrencyException::class)
        ->and($exception->getMessage())->toBe("In memory concurrency detected for stream $linkTo");
})->with('delete projection with emitted events');
