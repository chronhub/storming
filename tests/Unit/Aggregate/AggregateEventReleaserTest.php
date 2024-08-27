<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Aggregate;

use Storm\Aggregate\AggregateEventReleaser;
use Storm\Aggregate\GenericAggregateId;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\Decorator\NoOpMessageDecorator;
use Storm\Tests\Stubs\AggregateRootStub;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

beforeEach(function () {
    $this->messageDecorator = new NoOpMessageDecorator;
    $this->aggregateId = GenericAggregateId::fromString('407eec69-c268-49f3-8f27-3e5fefc8c15d');
    $this->aggregate = AggregateRootStub::create($this->aggregateId);
    $this->eventReleaser = new AggregateEventReleaser($this->messageDecorator);
});

test('return early when release events is empty', function () {
    $messageDecorator = mock(MessageDecorator::class);
    $messageDecorator->shouldNotReceive('decorate');

    $eventReleaser = new AggregateEventReleaser($messageDecorator);

    expect($eventReleaser->release($this->aggregate))->toBeEmpty();
});

test('release and decorate event', function () {
    $event = SomeEvent::fromContent(['foo' => 'bar']);

    $this->aggregate->next($event);

    expect($this->aggregate->getRecordedEvents())->toBe([$event])
        ->and($this->aggregate->getAppliesCount())->toBe(1)
        ->and($this->aggregate->version())->toBe(1);

    $events = $this->eventReleaser->release($this->aggregate);

    expect($events)->toHaveCount(1);

    $decoratedEvent = $events[0];

    expect($decoratedEvent)->toBeInstanceOf(SomeEvent::class)
        ->and($decoratedEvent->header(EventHeader::AGGREGATE_ID))->toBe($this->aggregateId->toString())
        ->and($decoratedEvent->header(EventHeader::AGGREGATE_ID_TYPE))->toBe(GenericAggregateId::class)
        ->and($decoratedEvent->header(EventHeader::AGGREGATE_TYPE))->toBe(AggregateRootStub::class)
        ->and($decoratedEvent->header(EventHeader::AGGREGATE_VERSION))->toBe(1);
});

test('increment event header version from current aggregate version', function () {
    expect($this->aggregate->version())->toBe(0);

    $this->aggregate->next(SomeEvent::fromContent(['foo' => 'bar']));
    $this->aggregate->next(SomeEvent::fromContent(['foo' => 'bar']));
    $this->aggregate->next(SomeEvent::fromContent(['foo' => 'bar']));

    expect($this->aggregate->version())->toBe(3);

    $events = $this->eventReleaser->release($this->aggregate);
    expect($events)->toHaveCount(3)
        ->and($this->aggregate->version())->toBe(3);

    $this->aggregate->next(SomeEvent::fromContent(['foo' => 'bar']));

    expect($this->aggregate->version())->toBe(4);

    $events = $this->eventReleaser->release($this->aggregate);

    expect($events)->toHaveCount(1)
        ->and($this->aggregate->version())->toBe(4)
        ->and($events[0]->header(EventHeader::AGGREGATE_VERSION))->toBe(4);
});
