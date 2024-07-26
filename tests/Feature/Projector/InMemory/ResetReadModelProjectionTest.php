<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Operations;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\ReadModelScope;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryProjectionExpectationTrait;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryReadModelProjectionTestBaseTrait;
use Storm\Tests\Feature\Projector\InMemory\InMemoryTestingFactory;

use function count;

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

test('resets the read model projection', function () {
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

    // reset read model and reset user state
    $this->projector->reset();

    expect($this->readModel->getContainer())->toBeEmpty();
    $this->assertProjectionExists($projectionName, true);
    $this->assertProjectionState(['total' => 0]);

    // run again
    $this->projector->run(false);

    ($this->assertReadModelBalance)(100);
    $this->assertProjectionExists($projectionName, true);
    $this->assertPartialProjectionState('total', 100);
});

test('resets from monitoring within the projection instance', function () {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $monitor = $this->factory->monitor();
    $hasReset = false;

    $reactors = function (EventScope $scope) use ($monitor, &$hasReset): void {
        $callback = function (DomainEvent $event, ReadModelScope $scope, UserStateScope $userState): void {
            $field = 'total';
            $id = $event->toContent()['id'];

            if ($event instanceof BalanceCreated) {
                $userState->upsert($field, $event->amount());
                $scope->stack('insert', $id, [$field => $event->amount()]);
            }

            if ($event instanceof BalanceAdded) {
                $userState->increment($field, $event->amount());
                $scope->stack('increment', $id, $field, $event->amount());
            }

            if ($event instanceof BalanceSubtracted) {
                $userState->decrement($field, $event->amount());
                $scope->stack('decrement', $id, $field, $event->amount());
            }

            $userState->merge('events', [$event::class]);
        };

        $scope
            ->ackOneOf(BalanceCreated::class, BalanceAdded::class, BalanceSubtracted::class)
            ->then($callback);

        if (! $hasReset && count($scope->userState['events']) === 4) {
            $monitor->markAsReset('balance');
            expect($monitor->statusOf('balance'))->toBe(ProjectionStatus::RESETTING->value);
            $hasReset = true;
        }
    };

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(inBackground: false);

    expect($this->readModel->getContainer())->toBeEmpty();
    $this->assertProjectionExists($projectionName, true);
    $this->assertProjectionState(['total' => 0]);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);

    // todo assert report time
    // sleep(1);

    $this->projector->run(false);

    ($this->assertReadModelBalance)(100);
    $this->assertProjectionExists($projectionName, true);
    $this->assertPartialProjectionState('total', 100);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);
});
