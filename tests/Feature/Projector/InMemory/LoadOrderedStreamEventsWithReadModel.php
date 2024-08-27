<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory;

use Generator;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Projector\Scope\ReadModelScope;
use Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Storm\Tests\Domain\Balance\BalanceAdded;
use Storm\Tests\Domain\Balance\BalanceCreated;
use Storm\Tests\Domain\Balance\BalanceId;
use Storm\Tests\Domain\Balance\BalanceSubtracted;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryProjectionExpectationTrait;
use Storm\Tests\Feature\Projector\InMemory\Concern\InMemoryReadModelProjectionTestBaseTrait;
use Storm\Tests\Feature\Projector\InMemory\Factory\InMemoryTestingFactory;

use function count;
use function iterator_to_array;

uses(
    InMemoryReadModelProjectionTestBaseTrait::class,
    InMemoryProjectionExpectationTrait::class
);

beforeEach(function () {
    $this->factory = new InMemoryTestingFactory();
    $this->readModel = new InMemoryReadModel();
});

test('order stream events across multiple streams', function () {
    $this->setupProjection(
        [
            [$b1 = 'b1', $bid1 = BalanceId::create()],
            [$b2 = 'b2', $bid2 = BalanceId::create()],
            [$b3 = 'b3', $bid3 = BalanceId::create()],
        ],
        projectionName: 'balance',
        options: ['retries' => [1, 2]],
    );

    $b1Events = iterator_to_array(getBalanceOne($bid1));
    foreach ($b1Events as $event) {
        $this->balanceEventStore($b1)->appendEvent($event);
    }

    $b2Events = iterator_to_array(getBalanceTwo($bid2));
    foreach ($b2Events as $event) {
        $this->balanceEventStore($b2)->appendEvent($event);
    }

    $b3Events = iterator_to_array(getBalanceThree($bid3));
    foreach ($b3Events as $event) {
        $this->balanceEventStore($b3)->appendEvent($event);
    }

    $thenReactor = function (ReadModelScope $scope): void {
        $eventTime = $scope->event()->header(Header::EVENT_TIME);
        $scope->userState()->push('streams', [$scope->streamName() => $eventTime]);

        if (count($scope->userState()['streams']) === 8) {
            $scope->stop();
        }
    };

    $this->projector
        ->initialize(fn (): array => ['total' => 0, 'streams' => []])
        ->subscribeToStream($b1, $b2, $b3)
        ->when($this->getReadModelReactor(), $thenReactor)
        ->filter($this->factory->getQueryFilter())
        ->run(inBackground: true);

    $expectedOrderStreams = [
        ['b2' => '2024-06-20T10:21:05.000001'],
        ['b3' => '2024-06-20T10:22:05.000002'],
        ['b1' => '2024-06-20T10:23:05.000003'],
        ['b2' => '2024-06-20T10:24:05.000004'],
        ['b3' => '2024-06-20T10:25:05.000005'],
        ['b1' => '2024-06-20T10:26:05.000006'],
        ['b3' => '2024-06-20T10:27:05.000007'],
        ['b2' => '2024-06-20T10:28:05.000008'],
    ];

    expect($this->projector->getState()['streams'])->toBe($expectedOrderStreams)
        ->and($this->projector->getState()['total'])->toBe(400);
});

function getBalanceOne(BalanceId $balanceId): Generator
{
    yield BalanceCreated::withBalance($balanceId, 100)->withHeaders(
        [
            EventHeader::AGGREGATE_ID => $balanceId->toString(),
            EventHeader::AGGREGATE_VERSION => 1,
            EventHeader::INTERNAL_POSITION => 1,
            Header::EVENT_TIME => '2024-06-20T10:23:05.000003',
        ]
    );

    yield BalanceAdded::withBalance($balanceId, 100)->withHeaders(
        [
            EventHeader::AGGREGATE_ID => $balanceId->toString(),
            EventHeader::AGGREGATE_VERSION => 2,
            EventHeader::INTERNAL_POSITION => 2,
            Header::EVENT_TIME => '2024-06-20T10:26:05.000006',
        ]
    );

    return 2;
}

function getBalanceTwo(BalanceId $balanceId): Generator
{
    yield BalanceCreated::withBalance($balanceId, 100)->withHeaders(
        [
            EventHeader::AGGREGATE_ID => $balanceId->toString(),
            EventHeader::AGGREGATE_VERSION => 1,
            EventHeader::INTERNAL_POSITION => 1,
            Header::EVENT_TIME => '2024-06-20T10:21:05.000001',
        ]
    );

    yield BalanceSubtracted::withBalance($balanceId, 100)->withHeaders(
        [
            EventHeader::AGGREGATE_ID => $balanceId->toString(),
            EventHeader::AGGREGATE_VERSION => 2,
            EventHeader::INTERNAL_POSITION => 2,
            Header::EVENT_TIME => '2024-06-20T10:24:05.000004',
        ]
    );

    yield BalanceAdded::withBalance($balanceId, 100)->withHeaders(
        [
            EventHeader::AGGREGATE_ID => $balanceId->toString(),
            EventHeader::AGGREGATE_VERSION => 8,
            EventHeader::INTERNAL_POSITION => 8,
            Header::EVENT_TIME => '2024-06-20T10:28:05.000008',
        ]
    );

    return 3;
}

function getBalanceThree(BalanceId $balanceId): Generator
{
    yield BalanceCreated::withBalance($balanceId, 100)->withHeaders(
        [
            EventHeader::AGGREGATE_ID => $balanceId->toString(),
            EventHeader::AGGREGATE_VERSION => 1,
            EventHeader::INTERNAL_POSITION => 1,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000002',
        ]
    );

    yield BalanceAdded::withBalance($balanceId, 100)->withHeaders(
        [
            EventHeader::AGGREGATE_ID => $balanceId->toString(),
            EventHeader::AGGREGATE_VERSION => 2,
            EventHeader::INTERNAL_POSITION => 2,
            Header::EVENT_TIME => '2024-06-20T10:25:05.000005',
        ]
    );

    yield BalanceSubtracted::withBalance($balanceId, 100)->withHeaders(
        [
            EventHeader::AGGREGATE_ID => $balanceId->toString(),
            EventHeader::AGGREGATE_VERSION => 8,
            EventHeader::INTERNAL_POSITION => 8,
            Header::EVENT_TIME => '2024-06-20T10:27:05.000007',
        ]
    );

    return 3;
}
