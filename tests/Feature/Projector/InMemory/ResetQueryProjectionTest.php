<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Operations;

use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryProjectionExpectationTrait;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryQueryProjectionTestBaseTrait;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

uses(
    InMemoryQueryProjectionTestBaseTrait::class,
    InMemoryProjectionExpectationTrait::class
);

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory();
    $this->expectedStateEvents = [
        BalanceCreated::class,
        BalanceAdded::class,
        BalanceSubtracted::class,
        BalanceSubtracted::class,
    ];
});

test('resets query projection running once', function () {
    $this->setupProjection();

    $this->makeEventStore($streamName = 'balance', $balanceId = BalanceId::create())
        ->withBalanceCreated(1, 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->projector
        ->initialize(fn (): array => [])
        ->subscribeToStream($streamName)
        ->when($this->getQueryReactor(), $this->getThenReactor())
        ->filter($this->factory->getQueryFilter())
        ->run(false);

    $this->assertProjectionState(
        [
            'balances' => [$balanceId->toString() => 100],
            'events' => $this->expectedStateEvents,
        ]
    );

    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);

    $this->projector->reset();
    $this->assertProjectionState([]);

    // run again
    $this->projector->run(false);

    $this->assertProjectionState(
        [
            'balances' => [$balanceId->toString() => 100],
            'events' => $this->expectedStateEvents,
        ]
    );
});
