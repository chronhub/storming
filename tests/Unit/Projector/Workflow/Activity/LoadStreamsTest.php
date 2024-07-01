<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use Closure;
use Generator;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Clock\PointInTime;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Iterator\MergeStreamIterator;
use Storm\Projector\Workflow\Activity\LoadStreams;
use Storm\Projector\Workflow\Notification\Checkpoint\CurrentCheckpoint;
use Storm\Projector\Workflow\Notification\Stream\StreamIteratorSet;
use Storm\Stream\StreamName;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

beforeEach(function () {
    $this->chronicler = mock(Chronicler::class);
    $this->clock = new PointInTime();
    $this->hub = mock(NotificationHub::class);
});

function getInstance(int $loadLimiter, callable $queryFilterResolver): Closure
{
    return function ($that) use ($loadLimiter, $queryFilterResolver) {
        return new LoadStreams(
            $that->chronicler,
            $that->clock,
            $loadLimiter,
            $queryFilterResolver
        );
    };
}

test('set null on stream iterator set with empty checkpoints', function () {
    $instance = getInstance(10, fn () => null)($this);

    $this->hub->expects('expect')->with(CurrentCheckpoint::class)->andReturn([]);
    $this->hub->expects('notify')->with(StreamIteratorSet::class, null);
    $this->chronicler->expects('retrieveFiltered')->never();

    $return = $instance($this->hub, fn () => true);

    expect($return)->toBeTrue();
});

test('set null on stream iterator set with unique checkpoint which raise stream not found exception', function () {
    $queryFilter = mock(QueryFilter::class);
    $resolver = function (string $streamName, int $nextPosition, int $limit) use ($queryFilter) {
        expect($streamName)->toBe('stream-1')
            ->and($nextPosition)->toBe(1)
            ->and($limit)->toBe(10);

        return $queryFilter;
    };

    $instance = getInstance(10, $resolver)($this);

    $checkpoint = CheckpointFactory::fromEmpty('stream-1', 'created_at');

    $this->hub->expects('expect')->with(CurrentCheckpoint::class)->andReturn([
        'stream-1' => $checkpoint,
    ]);

    $this->hub->expects('notify')->with(StreamIteratorSet::class, null);

    $this->chronicler->expects('retrieveFiltered')
        ->withArgs(function (StreamName $streamName, QueryFilter $filter) use ($queryFilter) {
            expect($streamName->name)->toBe('stream-1')
                ->and($filter)->toBe($queryFilter);

            return true;
        })->andThrow(new StreamNotFound('stream not found'));

    $return = $instance($this->hub, fn () => true);

    expect($return)->toBeTrue();
});

test('set stream iterator with merge streams when discovering stream events', function () {
    $queryFilter = mock(QueryFilter::class);
    $resolver = function (string $streamName, int $nextPosition, int $limit) use ($queryFilter) {
        if ($streamName === 'stream-1') {
            expect($streamName)->toBe('stream-1')
                ->and($nextPosition)->toBe(2)
                ->and($limit)->toBe(10);
        } else {
            expect($streamName)->toBe('stream-2')
                ->and($nextPosition)->toBe(6)
                ->and($limit)->toBe(10);
        }

        return $queryFilter;
    };

    $instance = getInstance(10, $resolver)($this);

    $this->hub->expects('expect')->once()->with(CurrentCheckpoint::class)->andReturn(getCheckpoints());

    $this->chronicler->expects('retrieveFiltered')->twice()
        ->withArgs(function (StreamName $streamName, QueryFilter $filter) use ($queryFilter) {
            expect($streamName->name)->toBeIn(['stream-1', 'stream-2'])
                ->and($filter)->toBe($queryFilter);

            return true;
        })->andReturn(getEventsForStream1(), getEventsForStream2());

    $this->hub->expects('notify')->withArgs(function (string $notification, $streams) {
        expect($notification)->toBe(StreamIteratorSet::class)
            ->and($streams)->toBeInstanceOf(MergeStreamIterator::class)
            ->and($streams->count())->toBe(2)
            ->and($streams->numberOfIterators)->toBe(2)
            ->and($streams->numberOfEvents)->toBe(2)
            ->and($streams->valid())->toBeTrue()
            ->and($streams->current())->toBeInstanceOf(SomeEvent::class)
            ->and($streams->streamName())->toBe('stream-1')
            ->and($streams->key())->toBe(2);

        $streams->next();

        expect($streams->current())->toBeInstanceOf(SomeEvent::class)
            ->and($streams->streamName())->toBe('stream-2')
            ->and($streams->key())->toBe(6);

        return true;
    });

    $return = $instance($this->hub, fn () => true);

    expect($return)->toBeTrue();
});

test('set stream iterator with merge streams and keep iterating if stream not found raises ', function () {
    $queryFilter = mock(QueryFilter::class);
    $resolver = function (string $streamName, int $nextPosition, int $limit) use ($queryFilter) {
        if ($streamName === 'stream-1') {
            expect($streamName)->toBe('stream-1')
                ->and($nextPosition)->toBe(2)
                ->and($limit)->toBe(10);
        } else {
            expect($streamName)->toBe('stream-2')
                ->and($nextPosition)->toBe(6)
                ->and($limit)->toBe(10);
        }

        return $queryFilter;
    };

    $instance = getInstance(10, $resolver)($this);

    $this->hub->expects('expect')->once()->with(CurrentCheckpoint::class)->andReturn(getCheckpoints());

    $this->chronicler->expects('retrieveFiltered')
        ->withArgs(function (StreamName $streamName, QueryFilter $filter) use ($queryFilter) {
            expect($streamName->name)->toBe('stream-1')
                ->and($filter)->toBe($queryFilter);

            return true;
        })->andThrow(new StreamNotFound('stream not found'));

    $this->chronicler->expects('retrieveFiltered')
        ->withArgs(function (StreamName $streamName, QueryFilter $filter) use ($queryFilter) {
            expect($streamName->name)->toBe('stream-2')
                ->and($filter)->toBe($queryFilter);

            return true;
        })->andReturn(getEventsForStream2());

    $this->hub->expects('notify')->withArgs(function (string $notification, $streams) {
        expect($notification)->toBe(StreamIteratorSet::class)
            ->and($streams)->toBeInstanceOf(MergeStreamIterator::class)
            ->and($streams->count())->toBe(1)
            ->and($streams->numberOfIterators)->toBe(1)
            ->and($streams->numberOfEvents)->toBe(1)
            ->and($streams->valid())->toBeTrue()
            ->and($streams->current())->toBeInstanceOf(SomeEvent::class)
            ->and($streams->streamName())->toBe('stream-2')
            ->and($streams->key())->toBe(6);

        $streams->next();

        expect($streams->valid())->toBeFalse();

        return true;
    });

    $return = $instance($this->hub, fn () => true);

    expect($return)->toBeTrue();
});

function getCheckpoints(): array
{
    return [
        'stream-1' => CheckpointFactory::from(
            'stream-1',
            1,
            'event time',
            'created_at',
            [],
            null
        ),
        'stream-2' => CheckpointFactory::from(
            'stream-2',
            5,
            'event time',
            'created_at',
            [],
            null
        ),
    ];
}

function getEventsForStream1(): Generator
{
    $streamName = 'stream-1';

    yield from [
        SomeEvent::fromContent(['name' => $streamName])->withHeaders([
            Header::EVENT_TIME => '2024-06-20T10:22:05.000002',
            EventHeader::INTERNAL_POSITION => 2,
        ]),
    ];

    return 1;
}

function getEventsForStream2(): Generator
{
    $streamName = 'stream-2';

    yield from [
        SomeEvent::fromContent(['name' => $streamName])->withHeaders([
            Header::EVENT_TIME => '2024-06-20T10:22:05.000006',
            EventHeader::INTERNAL_POSITION => 6,
        ]),
    ];

    return 1;
}
