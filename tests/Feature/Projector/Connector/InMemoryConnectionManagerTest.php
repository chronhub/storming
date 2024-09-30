<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\Connector;

use Illuminate\Contracts\Events\Dispatcher;
use Storm\Chronicler\InMemory\InMemoryEventStreamProvider;
use Storm\Chronicler\InMemory\VersioningEventStore;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\ChroniclerDecorator;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\InMemoryChronicler;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Connector\ConnectorManager;
use Storm\Projector\Connector\InMemoryConnectionManager;
use Storm\Projector\Exception\ConfigurationViolation;
use Storm\Projector\Options\InMemoryFixedOption;
use Storm\Projector\Options\InMemoryOption;
use Storm\Projector\Options\Option;
use Storm\Projector\Repository\InMemoryProjectionProvider;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;

beforeEach(function () {
    $this->inMemoryConfig = [
        'chronicler' => 'chronicler.in_memory',
        'event_stream_provider' => 'event_stream.provider.in_memory',
        'query_filter' => InMemoryFromToPosition::class,
        'options' => InMemoryOption::class,
        'serializer' => 'projector.serializer.json',
        'dispatch_events' => false,
    ];

    expect(config('projector.connection.in_memory'))->toBe($this->inMemoryConfig);
});

test('can resolve a connector from a configuration', function () {
    /** @var ConnectorManager $connectorResolver */
    $connectorResolver = $this->app[ConnectorManager::class];

    $connector = $connectorResolver->connection('in_memory');

    expect($connector)->toBeInstanceOf(InMemoryConnectionManager::class)
        ->and($connector->eventStore())->toBeInstanceOf(InMemoryChronicler::class)
        ->and($connector->eventStore())->tobeInstanceOf(VersioningEventStore::class)
        ->and($connector->eventStreamProvider())->toBeInstanceOf(InMemoryEventStreamProvider::class)
        ->and($connector->projectionProvider())->toBeInstanceOf(InMemoryProjectionProvider::class)
        ->and($connector->queryFilter())->toBeInstanceOf(InMemoryQueryFilter::class)
        ->and($connector->queryFilter())->toBeInstanceOf(InMemoryFromToPosition::class)
        ->and($connector->serializer())->toBeInstanceOf(SymfonySerializer::class)
        ->and($connector->clock())->toBeInstanceOf(SystemClock::class)
        ->and($connector->dispatcher())->toBeNull();
});

test('assert resolved options', function () {
    /** @var ConnectorManager $connectorResolver */
    $connectorResolver = $this->app[ConnectorManager::class];
    $connector = $connectorResolver->connection('in_memory');

    $options = $connector->toOption();

    expect($options)->toEqual(new InMemoryOption);
});

test('config options can be merged with default options', function () {
    /** @var ConnectorManager $connectorResolver */
    $connectorResolver = $this->app[ConnectorManager::class];
    $connector = $connectorResolver->connection('in_memory');

    $options = $connector->toOption();
    expect($options->getSignal())->toBeFalse();

    $mergedOptions = $connector->toOption(['signal' => true]);
    expect($mergedOptions->getSignal())->toBeTrue();
});

test('immutable option class can not merge dynamic options', function () {
    $this->app['config']->set('projector.connection.in_memory.options', InMemoryFixedOption::class);

    /** @var ConnectorManager $connectorResolver */
    $connectorResolver = $this->app[ConnectorManager::class];
    $connector = $connectorResolver->connection('in_memory');

    $immutableOptions = $connector->toOption();
    expect($immutableOptions)->toBeInstanceOf(InMemoryFixedOption::class);

    $options = $connector->toOption();
    expect($options->getSignal())->toBeFalse();

    $mergedOptions = $connector->toOption(['signal' => true]);
    expect($mergedOptions->getSignal())->toBeFalse();
});

test('set laravel event dispatcher to decorate repository when dispatch_events option is true', function () {
    $this->app['config']->set('projector.connection.in_memory.dispatch_events', true);

    /** @var ConnectorManager $connectorResolver */
    $connectorResolver = $this->app[ConnectorManager::class];

    $connector = $connectorResolver->connection('in_memory');

    expect($connector->dispatcher())->toBeInstanceOf(Dispatcher::class);
});

/**
 * projection should never be instantiated with a decorated event store,
 * as transactional or Eventable.
 */
test('retrieve inner chronicler', function () {
    $decoratedEventStore = mock(ChroniclerDecorator::class, InMemoryChronicler::class);
    $innerEventStore = mock(InMemoryChronicler::class);
    $decoratedEventStore->expects('innerChronicler')->andReturn($innerEventStore);

    $connectorManager = new InMemoryConnectionManager(
        $decoratedEventStore,
        mock(EventStreamProvider::class),
        mock(ProjectionProvider::class),
        mock(QueryFilter::class),
        mock(SystemClock::class),
        mock(SymfonySerializer::class),
        mock(Option::class),
        dispatcher: null,
    );

    expect($connectorManager->eventStore())->toBe($innerEventStore);
});

test('raise exception if inner chronicler is not an instance of InMemoryChronicler', function () {
    $eventStore = mock(ChroniclerDecorator::class, InMemoryChronicler::class);
    $innerEventStore = mock(Chronicler::class);
    $eventStore->expects('innerChronicler')->andReturn($innerEventStore);

    new InMemoryConnectionManager(
        $eventStore,
        mock(EventStreamProvider::class),
        mock(ProjectionProvider::class),
        mock(QueryFilter::class),
        mock(SystemClock::class),
        mock(SymfonySerializer::class),
        mock(Option::class),
        dispatcher: null,
    );
})->throws(ConfigurationViolation::class, 'Chronicler must be an instance of '.InMemoryChronicler::class);
