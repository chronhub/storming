<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Illuminate\Contracts\Foundation\Application;
use Storm\Chronicler\EventStoreBridge;
use Storm\Contract\Chronicler\InMemoryChronicler;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Store\InMemoryProjectionProvider;
use Storm\Serializer\SerializerFactory;

final readonly class InMemoryConnector implements Connector
{
    public function __construct(
        private Application $app,
        private SerializerFactory $serializerFactory,
        private EventStoreBridge $projectorBridge,
    ) {}

    /**
     * @param array{
     *     'chronicler': string,
     *     'event_stream_provider': string,
     *     'serializer': string,
     *     'options': string,
     *     'query_filter': string,
     *     'dispatch_events'?: bool,
     * } $config
     */
    public function connect(array $config): ConnectionManager
    {
        $clock = $this->app[SystemClock::class];
        $projectionProvider = new InMemoryProjectionProvider($clock);
        $useEvents = $config['dispatch_events'] ?? false;

        /** @var InMemoryChronicler $eventStore */
        $eventStore = $this->projectorBridge->getEventStore($config['chronicler']);
        $configSerializer = $this->app['config']->get('projector.serializer.'.$config['serializer']);

        return new InMemoryConnectionManager(
            $eventStore,
            $eventStore->getEventStreamProvider(),
            $projectionProvider,
            $this->app[$config['query_filter']],
            $clock,
            $this->serializerFactory->create($configSerializer),
            $this->app[$config['options']],
            $useEvents ? $this->app['events'] : null,
        );
    }
}
