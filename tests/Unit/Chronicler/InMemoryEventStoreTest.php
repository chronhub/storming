<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Chronicler;

use Generator;
use Storm\Aggregate\Identity\GenericAggregateId;
use Storm\Chronicler\Direction;
use Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Chronicler\InMemory\EventStreamInMemoryProvider;
use Storm\Chronicler\InMemory\VersioningEventStore;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Chronicler\InMemoryChronicler;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Symfony\Component\Uid\Uuid;

use function array_reverse;
use function array_shift;
use function count;
use function iterator_to_array;
use function range;

beforeEach(function () {
    $this->eventStreamProvider = new EventStreamInMemoryProvider;
    $this->eventStore = new VersioningEventStore($this->eventStreamProvider);
    $this->aggregateId = GenericAggregateId::fromString(Uuid::v4()->jsonSerialize());
});

dataset('stream names', [
    'stream name' => ['stream1'],
    'partition stream' => ['partition-foo'],
    'internal stream position' => ['$stream1'],
]);

dataset('direction', [Direction::FORWARD, Direction::BACKWARD]);

function generateStreamEvents(AggregateIdentity $identity, $count = 5): Generator
{
    $events = [];

    for ($i = 0; $i < $count; $i++) {
        $events[] = SomeEvent::fromContent(['foo' => 'bar'])
            ->withHeaders([
                EventHeader::AGGREGATE_ID => $identity->toString(),
                EventHeader::AGGREGATE_VERSION => $i + 1,
            ]);
    }

    yield from $events;

    return $count;
}

function assertInternalPositionsInOrder(array $order, Generator $events): void
{
    $countEvents = count($order);

    foreach ($events as $event) {
        $expectedPosition = array_shift($order);
        expect($event->header(EventHeader::AGGREGATE_VERSION))->toBe($expectedPosition)
            ->and($event->header(EventHeader::INTERNAL_POSITION))->toBe($expectedPosition);
    }

    expect($events->getReturn())->toBe($countEvents);
}

test('default instance', function () {
    expect($this->eventStore)->toBeInstanceOf(InMemoryChronicler::class)
        ->and($this->eventStore->getStreams())->toBeEmpty();
});

test('append stream without event', function (string $streamName) {
    $stream = new Stream(new StreamName($streamName));

    expect($this->eventStore->hasStream($stream->name))->toBeFalse();

    $this->eventStore->append($stream);

    expect($this->eventStore->hasStream($stream->name))->toBeTrue();
})->with('stream names');

test('retrieve all stream events', function (string $streamName, Direction $direction) {
    $events = iterator_to_array(generateStreamEvents($this->aggregateId));
    $stream = new Stream(new StreamName($streamName), $events);

    expect($this->eventStore->hasStream($stream->name))->toBeFalse();

    $this->eventStore->append($stream);

    expect($this->eventStore->hasStream($stream->name))->toBeTrue();

    $streamEvents = $this->eventStore->retrieveAll($stream->name, $this->aggregateId, $direction);

    $expectedPositions = range(1, count($events));
    if ($direction === Direction::BACKWARD) {
        $expectedPositions = array_reverse($expectedPositions);
    }

    assertInternalPositionsInOrder($expectedPositions, $streamEvents);
})
    ->with('stream names')
    ->with('direction');

test('raise stream not found exception on retrieve all', function () {
    expect($this->eventStore->hasStream(new StreamName('stream1')))->toBeFalse();

    $this->eventStore->retrieveAll(new StreamName('stream1'), $this->aggregateId);
})->throws(StreamNotFound::class, 'Stream stream1 not found');

test('raise no stream event return exception on retrieve all', function () {
    $stream = new Stream(new StreamName('stream1'));

    $this->eventStore->append($stream);

    expect($this->eventStore->hasStream(new StreamName('stream1')))->toBeTrue();

    $this->eventStore->retrieveAll($stream->name, $this->aggregateId)->current();
})->throws(NoStreamEventReturn::class, 'Stream stream1 not found');

test('raise stream not found exception on retrieve filtered', function () {
    expect($this->eventStore->hasStream(new StreamName('stream1')))->toBeFalse();

    $this->eventStore->retrieveFiltered(new StreamName('stream1'), mock(InMemoryQueryFilter::class));
})->throws(StreamNotFound::class, 'Stream stream1 not found');

test('raise no stream event return on retrieve filtered', function () {
    expect($this->eventStore->hasStream(new StreamName('stream1')))->toBeFalse();

    $stream = new Stream(new StreamName('stream1'));
    $queryFilter = mock(InMemoryQueryFilter::class);
    $queryFilter->shouldReceive('orderBy')->andReturn(Direction::FORWARD);
    $queryFilter->shouldReceive('apply')->andReturn(fn () => null);

    $this->eventStore->append($stream);

    expect($this->eventStore->hasStream(new StreamName('stream1')))->toBeTrue();

    $this->eventStore->retrieveFiltered($stream->name, $queryFilter)->current();
})->throws(NoStreamEventReturn::class, 'Stream stream1 not found');

test('retrieve filtered stream events', function (string $streamName, Direction $direction) {
    $queryFilter = new readonly class($direction) implements InMemoryQueryFilter
    {
        public function __construct(private Direction $direction) {}

        public function orderBy(): Direction
        {
            return $this->direction;
        }

        public function apply(): callable
        {
            return function (DomainEvent $event): bool {
                return $event->header(EventHeader::AGGREGATE_VERSION) % 2 === 0;
            };
        }
    };

    $events = iterator_to_array(generateStreamEvents($this->aggregateId, 10));
    $stream = new Stream(new StreamName($streamName), $events);

    $this->eventStore->append($stream);

    $expectedPositions = [2, 4, 6, 8, 10];
    if ($direction === Direction::BACKWARD) {
        $expectedPositions = array_reverse($expectedPositions);
    }

    $streamEvents = $this->eventStore->retrieveFiltered($stream->name, $queryFilter);

    assertInternalPositionsInOrder($expectedPositions, $streamEvents);
})
    ->with('stream names')
    ->with('direction');

test('delete stream', function () {
    $streams = ['stream-1', 'stream-2', 'stream-3'];

    foreach ($streams as $stream) {
        $this->eventStore->append(new Stream(new StreamName($stream)));
    }

    expect($this->eventStore->getStreams())->toHaveCount(3)
        ->and($this->eventStore->getStreams()->keys()->toArray())->toBe($streams);

    $this->eventStore->delete(new StreamName('stream-2'));

    expect($this->eventStore->getStreams())->toHaveCount(2)
        ->and($this->eventStore->getStreams()->keys()->toArray())->not->toContain('stream-2')
        ->and($this->eventStore->hasStream(new StreamName('stream-2')))->toBeFalse();

    $this->eventStore->delete(new StreamName('stream-1'));
    expect($this->eventStore->getStreams())->toHaveCount(1)
        ->and($this->eventStore->getStreams()->keys()->toArray())->not->toContain('stream-1')
        ->and($this->eventStore->hasStream(new StreamName('stream-1')))->toBeFalse();

    $this->eventStore->delete(new StreamName('stream-3'));
    expect($this->eventStore->getStreams())->toBeEmpty();
});

test('raise exception when stream not found on delete', function () {
    $this->eventStore->delete(new StreamName('stream-1'));
})->throws(StreamNotFound::class, 'Stream stream-1 not found');

test('filter stream', function (array $filter, array $expected) {
    expect($this->eventStore->getStreams())->toBeEmpty();

    foreach ($expected as $stream) {
        $this->eventStore->append(new Stream(new StreamName($stream)));
    }

    expect($this->eventStore->filterStreams(...$filter))->toBe($expected)
        ->and($this->eventStore->filterPartitions(...$filter))->toBeEmpty();
})
    ->with([
        ['filter' => [], 'expected' => []],
        ['filter' => ['stream1'], 'expected' => ['stream1']],
        ['filter' => ['stream1', 'stream3'], 'expected' => ['stream1', 'stream3']],
        ['filter' => ['stream1', 'stream2', 'stream3'], 'expected' => ['stream1', 'stream2', 'stream3']],
        ['filter' => ['stream4'], 'expected' => []],
        ['filter' => ['stream1', 'stream4'], 'expected' => ['stream1']],
        ['filter' => ['stream-1', 'stream4'], 'expected' => []],
    ]);

test('filter partitions by splitting stream name with first dash', function () {
    $partitions = ['stream-foo', 'stream-bar', 'stream-foobar', 'another_stream-foo'];

    expect($this->eventStore->getStreams())->toBeEmpty();

    foreach ($partitions as $category) {
        $this->eventStore->append(new Stream(new StreamName($category)));
    }

    expect($this->eventStore->filterPartitions('partition-1'))->toBeEmpty()
        ->and($this->eventStore->filterPartitions('stream'))->toBe(['stream-foo', 'stream-bar', 'stream-foobar'])
        ->and($this->eventStore->filterPartitions('another_stream'))->toBe(['another_stream-foo'])
        ->and($this->eventStore->filterPartitions('stream-foo', 'stream-bar'))->toBeEmpty()
        ->and($this->eventStore->filterPartitions('stream-foo', 'stream-bar', 'stream-foobar'))->toBeEmpty()
        ->and($this->eventStore->filterStreams('stream-foo', 'stream-bar', 'stream-foobar', 'another_stream-foo'))->toBeEmpty();
});
