<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector;

use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Storm\Stream\StreamName;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Domain\BalanceEventStore;
use Storm\Tests\Domain\ProjectionBalanceReactor;
use Storm\Tests\Feature\InMemoryTestingFactory;

use function count;

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

dataset('retries', [
    'one retry' => [[1]],
    'two retries' => [[1, 2]],
    'three retries' => [[1, 2, 3]],
]);

test('read model projection', function () {
    $manager = $this->factory->createProjectorManager();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(2, 200)
        ->withBalanceSubtracted(3, 150)
        ->withBalanceSubtracted(4, 50);

    $projector = $manager->newReadModelProjector($streamName->name, $this->readModel);
    $reactors = ProjectionBalanceReactor::getReadModelReactors(keepRunning: false, stopAt: false);

    $projector
        ->initialize(fn (): array => ['balance' => 0])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    // assert projection state
    expect($projector->getState())->toBe([
        'balance' => 100,
        'events' => $this->expectedStateEvents,
    ]);

    // assert projection model
    $this->factory
        ->assertProjectionModel(
            streamName: 'balance',
            state: $this->factory->serializer->encode($projector->getState(), 'json'),
            status: ProjectionStatus::IDLE->value,
            lockedUntil: null
        )
        ->assertProjectionModelCheckpoint(
            streamName: 'balance',
            position: 4,
            gaps: [],
            gapType: null
        );

    // assert the read model
    expect($this->readModel->getContainer())->toBe([$balanceId->toString() => ['balance' => 100]]);

    // assert the projector report
    $report = $projector->getReport();

    expect($report['cycle'])->toBe(1)
        ->and($report['acked_event'])->toBe(4)
        ->and($report['total_event'])->toBe(4);
});

test('detect gaps with running in background or once and no retry', function (bool $keepRunning) {
    $manager = $this->factory->createProjectorManager();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(3, 200)
        ->withBalanceSubtracted(5, 150)
        ->withBalanceSubtracted(7, 50);

    $projector = $manager->newReadModelProjector($streamName->name, $this->readModel, ['retries' => []]);
    $reactors = ProjectionBalanceReactor::getReadModelReactors($keepRunning, 4);

    $projector
        ->initialize(fn (): array => ['balance' => 0])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run($keepRunning);

    // assert projection state
    expect($projector->getState())->toBe([
        'balance' => 100,
        'events' => $this->expectedStateEvents,
    ]);

    // assert projection model
    $this->factory
        ->assertProjectionModel(
            streamName: 'balance',
            state: $this->factory->serializer->encode($projector->getState(), 'json'),
            status: ProjectionStatus::IDLE->value,
            lockedUntil: null
        )
        ->assertProjectionModelCheckpoint(
            streamName: 'balance',
            position: 7,
            gaps: [2, 4, 6],
            gapType: GapType::IN_GAP
        );

    // assert the read model
    expect($this->readModel->getContainer())->toBe([$balanceId->toString() => ['balance' => 100]]);

    // assert the projector report
    $report = $projector->getReport();

    expect($report['cycle'])->toBe(1)
        ->and($report['acked_event'])->toBe(4)
        ->and($report['total_event'])->toBe(4);
})->with('keep projection running');

test('detect gaps with running in background and setup retries', function (array $retries) {
    $manager = $this->factory->createProjectorManager();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(3, 200)
        ->withBalanceSubtracted(5, 150)
        ->withBalanceSubtracted(7, 50);

    $projector = $manager->newReadModelProjector($streamName->name, $this->readModel, ['retries' => $retries]);
    $reactors = ProjectionBalanceReactor::getReadModelReactors(keepRunning: true, stopAt: 4);

    $projector
        ->initialize(fn (): array => ['balance' => 0])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(true);

    // assert projection state
    expect($projector->getState())->toBe([
        'balance' => 100,
        'events' => $this->expectedStateEvents,
    ]);

    // assert projection model
    $this->factory
        ->assertProjectionModel(
            streamName: 'balance',
            state: $this->factory->serializer->encode($projector->getState(), 'json'),
            status: ProjectionStatus::IDLE->value,
            lockedUntil: null
        )
        ->assertProjectionModelCheckpoint(
            streamName: 'balance',
            position: 7,
            gaps: [2, 4, 6],
            gapType: GapType::IN_GAP
        );

    // assert the read model
    expect($this->readModel->getContainer())->toBe([$balanceId->toString() => ['balance' => 100]]);

    // assert the projector report
    $report = $projector->getReport();

    // first cycle + number of retries * number of events which have gaps
    // we only expect one gap between each event
    $expectedCycles = 1 + count($retries) * 3;

    expect($report['cycle'])->toBe($expectedCycles)
        ->and($report['acked_event'])->toBe(4)
        ->and($report['total_event'])->toBe(4);
})->with('retries');

/**
 * checkMe
 * when retry is set, the projector will retry the gap detection on the next run.
 * a range of gaps are considered as a single gap, and the attempt to fill the gap is considered as a single retry.
 */
test('detect larger gaps with running in background and setup retries', function (array $retries) {
    $manager = $this->factory->createProjectorManager();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(5, 200)
        ->withBalanceSubtracted(8, 150)
        ->withBalanceSubtracted(12, 50);

    $projector = $manager->newReadModelProjector($streamName->name, $this->readModel, ['retries' => $retries]);
    $reactors = ProjectionBalanceReactor::getReadModelReactors(keepRunning: true, stopAt: 4);

    $projector
        ->initialize(fn (): array => ['balance' => 0])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(true);

    // assert projection state
    expect($projector->getState())->toBe([
        'balance' => 100,
        'events' => $this->expectedStateEvents,
    ]);

    // assert projection model
    $this->factory
        ->assertProjectionModel(
            streamName: 'balance',
            state: $this->factory->serializer->encode($projector->getState(), 'json'),
            status: ProjectionStatus::IDLE->value,
            lockedUntil: null
        )
        ->assertProjectionModelCheckpoint(
            streamName: 'balance',
            position: 12,
            gaps: [2, 3, 4, 6, 7, 9, 10, 11],
            gapType: GapType::IN_GAP
        );

    // assert the read model
    expect($this->readModel->getContainer())->toBe([$balanceId->toString() => ['balance' => 100]]);

    // assert the projector report
    $report = $projector->getReport();

    // first cycle + number of retries * number of events which have gaps
    $expectedCycles = 1 + count($retries) * 3;

    expect($report['cycle'])->toBe($expectedCycles)
        ->and($report['acked_event'])->toBe(4)
        ->and($report['total_event'])->toBe(4);
})->with('retries');

/**
 * when retry is set, the projector will retry the gap detection on the next run.
 * and the projector will stop as it runs only once.
 */
test('fails detect gaps with running once and setup retries', function (array $retries) {
    $manager = $this->factory->createProjectorManager();

    $balanceId = BalanceId::create();
    $streamName = new StreamName('balance');
    $store = new BalanceEventStore($this->factory->chronicler, $streamName, $balanceId);
    $store
        ->withBalanceCreated(1, 100)
        ->withBalanceAdded(3, 200)
        ->withBalanceSubtracted(5, 150)
        ->withBalanceSubtracted(7, 50);

    $projector = $manager->newReadModelProjector($streamName->name, $this->readModel, ['retries' => $retries]);
    $reactors = ProjectionBalanceReactor::getReadModelReactors(keepRunning: false, stopAt: false);

    $projector
        ->initialize(fn (): array => ['balance' => 0])
        ->subscribeToStream('balance')
        ->when($reactors)
        ->filter($this->factory->queryScope->fromIncludedPosition())
        ->run(false);

    expect($projector->getState())->toBe(['balance' => 100, 'events' => [BalanceCreated::class]]);

    // assert projection report
    $report = $projector->getReport();

    expect($report['cycle'])->toBe(1)
        ->and($report['acked_event'])->toBe(1)
        ->and($report['total_event'])->toBe(1);
})->with('retries');
