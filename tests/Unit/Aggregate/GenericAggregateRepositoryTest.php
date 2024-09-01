<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Aggregate;

use Generator;
use Storm\Aggregate\AggregateEventReleaser;
use Storm\Aggregate\DefaultAggregateRepository;
use Storm\Aggregate\GenericAggregateId;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Message\EventHeader;
use Storm\Message\Decorator\NoOpMessageDecorator;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;
use Storm\Tests\Stubs\AggregateRootStub;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\StreamEventsGeneratorStub;

use function iterator_to_array;

beforeEach(function () {
    $this->eventsGenerator = new StreamEventsGeneratorStub;
    $this->chronicler = mock(Chronicler::class);
    $this->streamName = new StreamName('test');
    $this->aggregateId = GenericAggregateId::fromString('407eec69-c268-49f3-8f27-3e5fefc8c15d');
    $this->eventReleaser = new AggregateEventReleaser(new NoOpMessageDecorator);
    $this->aggregateRepository = new DefaultAggregateRepository(
        $this->chronicler,
        $this->streamName,
        $this->eventReleaser
    );
});

test('retrieve aggregate from aggregate id', function () {
    $events = $this->eventsGenerator->generateEvents(
        headers: [EventHeader::AGGREGATE_TYPE => AggregateRootStub::class],
        count: 5
    );

    $this->chronicler->expects('retrieveAll')
        ->withArgs(function (StreamName $streamName, GenericAggregateId $aggregateId) {
            return $this->streamName->name === $streamName->name
                && $this->aggregateId->equalsTo($aggregateId);
        })->andReturns($events);

    $aggregate = $this->aggregateRepository->retrieve($this->aggregateId);

    expect($aggregate)->toBeInstanceOf(AggregateRootStub::class)
        ->and($aggregate->identity())->toBe($this->aggregateId)
        ->and($aggregate->version())->toBe(5);
});

test('retrieve null aggregate from aggregate identity when stream not found', function () {
    $events = $this->eventsGenerator->generateStreamNotFound($this->streamName->name);

    $this->chronicler->expects('retrieveAll')
        ->withArgs(function (StreamName $streamName, GenericAggregateId $aggregateId) {
            return $this->streamName->name === $streamName->name
                && $this->aggregateId->equalsTo($aggregateId);
        })->andReturns($events);

    $aggregate = $this->aggregateRepository->retrieve($this->aggregateId);

    expect($aggregate)->toBeNull();
});

test('retrieve null aggregate from aggregate identity when history of events is not valid', function () {
    $events = $this->eventsGenerator->generateDummyEvents();
    foreach ($events as $event) {
        expect($event)->toBeInstanceOf(SomeEvent::class);
    }

    expect($events->valid())->toBeFalse();

    $this->chronicler->expects('retrieveAll')
        ->withArgs(function (StreamName $streamName, GenericAggregateId $aggregateId) {
            return $this->streamName->name === $streamName->name
                && $this->aggregateId->equalsTo($aggregateId);
        })->andReturns($events);

    $aggregate = $this->aggregateRepository->retrieve($this->aggregateId);

    expect($aggregate)->toBeNull();
});

test('retrieve aggregate from aggregate id with query filter', function () {
    $events = $this->eventsGenerator->generateEvents(
        headers: [EventHeader::AGGREGATE_TYPE => AggregateRootStub::class],
        count: 5
    );

    $queryFilter = mock(QueryFilter::class);

    $this->chronicler->expects('retrieveFiltered')
        ->withArgs(function (StreamName $streamName, QueryFilter $filter) use ($queryFilter) {
            return $this->streamName->name === $streamName->name
                && $filter === $queryFilter;
        })->andReturns($events);

    $aggregate = $this->aggregateRepository->retrieveFiltered($this->aggregateId, $queryFilter);

    expect($aggregate)->toBeInstanceOf(AggregateRootStub::class)
        ->and($aggregate->identity())->toBe($this->aggregateId)
        ->and($aggregate->version())->toBe(5);
});

test('retrieve null aggregate from aggregate id with query filter when stream not found', function () {
    $events = $this->eventsGenerator->generateStreamNotFound($this->streamName->name);

    $queryFilter = mock(QueryFilter::class);

    $this->chronicler->expects('retrieveFiltered')
        ->withArgs(function (StreamName $streamName, QueryFilter $filter) use ($queryFilter) {
            return $this->streamName->name === $streamName->name
                && $filter === $queryFilter;
        })->andReturns($events);

    $aggregate = $this->aggregateRepository->retrieveFiltered($this->aggregateId, $queryFilter);

    expect($aggregate)->toBeNull();
});

test('retrieve null aggregate from aggregate identity with query filter when history of events is not valid', function () {
    $events = $this->eventsGenerator->generateDummyEvents();
    foreach ($events as $event) {
        expect($event)->toBeInstanceOf(SomeEvent::class);
    }

    expect($events->valid())->toBeFalse();

    $queryFilter = mock(QueryFilter::class);
    $this->chronicler->expects('retrieveFiltered')
        ->withArgs(function (StreamName $streamName, QueryFilter $filter) use ($queryFilter) {
            return $this->streamName->name === $streamName->name
                && $filter === $queryFilter;
        })->andReturns($events);

    $aggregate = $this->aggregateRepository->retrieveFiltered($this->aggregateId, $queryFilter);

    expect($aggregate)->toBeNull();
});

test('retrieve from history', function (?QueryFilter $queryFilter) {
    if ($queryFilter === null) {
        $this->chronicler->expects('retrieveAll')
            ->withArgs(function (StreamName $streamName, GenericAggregateId $aggregateId) {
                return $this->streamName->name === $streamName->name
                    && $this->aggregateId->equalsTo($aggregateId);
            })->andYield([]);
    } else {
        $this->chronicler->expects('retrieveFiltered')
            ->withArgs(function (StreamName $streamName, QueryFilter $filter) use ($queryFilter) {
                return $this->streamName->name === $streamName->name
                    && $filter === $queryFilter;
            })->andYield([]);
    }

    $streamEvents = $this->aggregateRepository->retrieveHistory($this->aggregateId, $queryFilter);

    expect($streamEvents)->toBeInstanceOf(Generator::class);
})->with([
    'no query filter' => [null],
    'with query filter' => [mock(QueryFilter::class)],
]);

test('store aggregate with events to release and decorate events', function () {
    $event = SomeEvent::fromContent(['name' => 'john']);

    $aggregate = AggregateRootStub::create($this->aggregateId);
    $aggregate->next($event);

    expect($aggregate->version())->toBe(1)
        ->and($aggregate->getRecordedEvents())->toBe([$event]);

    $this->chronicler->expects('append')
        ->withArgs(function (Stream $stream) {
            $events = iterator_to_array($stream->events());

            return $stream->name->name === $this->streamName->name
                && $events[0]->header(EventHeader::AGGREGATE_TYPE) === AggregateRootStub::class
                && $events[0]->header(EventHeader::AGGREGATE_ID) === $this->aggregateId->toString()
                && $events[0]->header(EventHeader::AGGREGATE_ID_TYPE) === $this->aggregateId::class
                && $events[0]->header(EventHeader::AGGREGATE_VERSION) === 1;
        });

    $this->aggregateRepository->store($aggregate);
});

test('return early on store when no events to release', function () {
    $aggregate = AggregateRootStub::create($this->aggregateId);
    expect($aggregate->getRecordedEvents())->toBe([]);

    $this->chronicler->expects('append')->never();

    $this->aggregateRepository->store($aggregate);
});
