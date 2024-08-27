<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Aggregate;

use Generator;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Tests\Stubs\AggregateRootStub;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\StreamEventsGeneratorStub;

use function iterator_to_array;

beforeEach(function () {
    $this->aggregateId = mock(AggregateIdentity::class);
    $this->eventStub = new StreamEventsGeneratorStub();
});

test('default instance', function () {
    $aggregate = AggregateRootStub::create($this->aggregateId);

    expect($aggregate->identity())->toBe($this->aggregateId)
        ->and($aggregate->version())->toBe(0)
        ->and($aggregate->getRecordedEvents())->toBe([])
        ->and($aggregate->getAppliesCount())->toBe(0);
});

test('add event', function () {
    $aggregate = AggregateRootStub::create($this->aggregateId);

    $event = iterator_to_array($this->eventStub->generateDummyEvents());

    $aggregate->next($event[0]);

    expect($aggregate->getRecordedEvents())->toBe($event)
        ->and($aggregate->getAppliesCount())->toBe(1)
        ->and($aggregate->version())->toBe(1);
});

test('add events', function () {
    $aggregate = AggregateRootStub::create($this->aggregateId);

    $events = iterator_to_array($this->eventStub->generateDummyEvents(10));
    foreach ($events as $event) {
        $aggregate->next($event);
    }

    expect($aggregate->getRecordedEvents())->toBe($events)
        ->and($aggregate->getAppliesCount())->toBe(10)
        ->and($aggregate->version())->toBe(10);
});

test('release events', function () {
    $aggregate = AggregateRootStub::create($this->aggregateId);

    $events = iterator_to_array($this->eventStub->generateDummyEvents(10));
    foreach ($events as $event) {
        $aggregate->next($event);
    }

    $releasedEvents = $aggregate->releaseEvents();

    expect($releasedEvents)->toBe($events)
        ->and($aggregate->getRecordedEvents())->toBe([])
        ->and($aggregate->getAppliesCount())->toBe(10)
        ->and($aggregate->version())->toBe(10);
});

test('reconstitute aggregate', function () {
    $aggregate = AggregateRootStub::create($this->aggregateId);

    $events = $this->eventStub->generateDummyEvents(10);

    $reconstituted = AggregateRootStub::reconstitute($this->aggregateId, $events);

    expect($reconstituted)->not->toEqual($aggregate)
        ->and($reconstituted->getRecordedEvents())->toBeEmpty()
        ->and($reconstituted->getAppliesCount())->toBe(10)
        ->and($reconstituted->version())->toBe(10);
});

test('fails reconstitute aggregate with invalid return generator', function (int $return) {
    $events = generateInvalidReturnEvents($return);

    $reconstituted = AggregateRootStub::reconstitute($this->aggregateId, $events);

    expect($reconstituted)->toBeNull();
})->with([[-1], [0]]);

function generateInvalidReturnEvents(int $return): Generator
{
    yield SomeEvent::fromContent([]);

    return $return;
}
