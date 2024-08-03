<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Exception;
use Storm\Clock\Clock;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\ProjectorScope;
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

use function array_merge;
use function count;

uses(
    InMemoryReadModelProjectionTestBaseTrait::class,
    InMemoryProjectionExpectationTrait::class
);

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory();
    $this->readModel = new InMemoryReadModel();
    $this->expectedStateEvents = [
        BalanceCreated::class,
        BalanceAdded::class,
        BalanceSubtracted::class,
        BalanceSubtracted::class,
    ];
});

test('reads events from the beginning of the stream', function (?string $descriptionId) {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
        descriptionId: $descriptionId,
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

    $this->assertProjectionState(['total' => 100, 'events' => $this->expectedStateEvents]);
    $this->assertReadModelBalance($streamName, 100);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 4);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4, descriptionId: $descriptionId);
})->with('projection optional description id');

test('run projection again from last position kept in memory', function () {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[2, 200], [3, -150], [4, -50]]);

    $reactors = $this->getReadModelReactor();

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->run(inBackground: false);

    $this->assertProjectionState(['total' => 100, 'events' => $this->expectedStateEvents]);
    $this->assertReadModelBalance($streamName, 100);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 4);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);

    // append events to the stream
    $this->balanceEventStore($streamName)
        ->withBalanceAdded(version: 5, amount: 600)
        ->withBalanceSubtracted(version: 6, amount: 400);

    // run projection again
    $this->projector->run(inBackground: false);

    $expectedStateEvents = array_merge($this->expectedStateEvents, [BalanceAdded::class, BalanceSubtracted::class]);

    $this->assertProjectionState(['total' => 300, 'events' => $expectedStateEvents]);
    $this->assertReadModelBalance($streamName, 300);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 6);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 2, totalEvent: 2);
});

test('read model scope with one processed event', function () {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance'
    );

    $this->balanceEventStore($streamName)->withBalanceCreated(version: 1, amount: 100);

    $reactors = function (EventScope $scope): void {
        $scope
            ->ack(BalanceCreated::class)
            ->then(function (DomainEvent $event, ProjectorScope $scope, ?UserStateScope $userState): void {
                /** @var ReadModelScope $scope */
                expect($userState)->toBeNull()
                    ->and($scope->streamName())->toBe('account')
                    ->and($scope)->toBeInstanceOf(ReadModelScope::class)
                    ->and($scope->readModel())->toBe($this->readModel)
                    ->and($scope->clock())->toBeInstanceOf(Clock::class);
            });
    };

    $this->projector
        ->subscribeToStream($streamName)
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->run(inBackground: false);

    expect($this->projector->getName())->toBe($projectionName);
});

test('reactors should never been called when no event is processed', function () {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance'
    );

    $reactors = function (): void {
        throw new Exception('should never been called');
    };

    $this->projector
        ->subscribeToStream($streamName)
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(inBackground: false);

    $this->assertProjectionState([]);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
});

test('does not detect gaps with no retry', function (bool $keepRunning) {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance'
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[3, 200], [5, -150], [7, -25]]);

    $reactors = $this->getReadModelReactor(keepRunning: true, stopAt: ['events', 4]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->run(inBackground: $keepRunning);

    $this->assertProjectionState(['total' => 125, 'events' => $this->expectedStateEvents]);
    $this->assertReadModelBalance($streamName, 125);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 7);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 4, totalEvent: 4);
})->with('keep projection running');

test('detect gaps with setup retries and record gap', function (array $retries, bool $recordGap) {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
        options: ['retries' => $retries, 'recordGap' => $recordGap]
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[3, 200], [5, -150], [10, -50]]);

    $reactors = $this->getReadModelReactor(keepRunning: true, stopAt: ['events', 4]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->run(inBackground: true);

    $this->assertProjectionState(['total' => 100, 'events' => $this->expectedStateEvents]);
    $this->assertReadModelBalance($streamName, 100);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);

    $expectedGaps = $recordGap ? [2, 4, [6, 9]] : [];
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 10, gaps: $expectedGaps);

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

test('detect larger gaps with setup retries and record gap with range threshold', function (array $retries, bool $recordGap) {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
        options: ['retries' => $retries, 'recordGap' => $recordGap]
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withVersioningAmount([[3, 200], [25, -150], [70, -50]]);

    $reactors = $this->getReadModelReactor(keepRunning: true, stopAt: ['events', 4]);

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream($streamName)
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->run(inBackground: true);

    $this->assertProjectionState(['total' => 100, 'events' => $this->expectedStateEvents]);
    $this->assertReadModelBalance($streamName, 100);
    $this->assertProjectionModel(projectionName: $projectionName, status: ProjectionStatus::IDLE->value, lockedUntil: null);

    $expectedGaps = $recordGap ? [2, [4, 24], [26, 69]] : [];
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 70, gaps: $expectedGaps);

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

test('fails detect gaps with running once and setup retries', function (array $retries) {
    $this->setupProjection(
        streamName: $streamName = 'account',
        projectionName: $projectionName = 'balance',
        options: ['retries' => $retries]
    );

    $this->balanceEventStore($streamName)
        ->withBalanceCreated(version: 1, amount: 100)
        ->withBalanceAdded(version: 10, amount: 200);

    $reactors = $this->getReadModelReactor();

    $this->projector
        ->initialize(fn (): array => ['total' => 0])
        ->subscribeToStream('account')
        ->when($reactors)
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->run(inBackground: false);

    $this->assertProjectionState(['total' => 100, 'events' => [BalanceCreated::class]]);
    $this->assertReadModelBalance($streamName, 100);
    $this->assertProjectionModel(projectionName: 'balance', status: ProjectionStatus::IDLE->value, lockedUntil: null);
    $this->assertProjectionModelCheckpoint(projectionName: $projectionName, streamName: $streamName, position: 1);
    $this->assertProjectionReport(cycle: 1, ackedEvent: 1, totalEvent: 1);
})->with('projection options with non empty retries');
