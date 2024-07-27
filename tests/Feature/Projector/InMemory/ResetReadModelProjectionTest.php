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
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($this->getReadModelReactor())
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
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
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $monitor = $this->factory->monitor();
    $resetStatus = null;

    $reactors = function (EventScope $scope) use ($monitor, &$resetStatus): void {
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

        if ($resetStatus === null && count($scope->userState['events']) === 4) {
            $monitor->markAsReset('balance');
            $resetStatus = $monitor->statusOf('balance');
        }
    };

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
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
