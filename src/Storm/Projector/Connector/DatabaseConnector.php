<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Illuminate\Contracts\Foundation\Application;
use Storm\Chronicler\EventStoreBridge;
use Storm\Contract\Chronicler\DatabaseChronicler;
use Storm\Contract\Clock\SystemClock;
use Storm\Projector\Exception\ConfigurationViolation;
use Storm\Projector\Repository\DatabaseProjectionProvider;
use Storm\Serializer\SerializerFactory;

use function get_class;
use function sprintf;

final readonly class DatabaseConnector implements Connector
{
    public function __construct(
        private Application $app,
        private SerializerFactory $serializerFactory,
        private EventStoreBridge $projectorBridge,
    ) {}

    /**
     * @param array{
     *     'connection': string,
     *     'table_name'?: string,
     *     'chronicler': string,
     *     'event_stream_provider': string,
     *     'query_filter': string,
     *     'serializer': string,
     *     'options': string,
     *     'dispatch_events'?: bool,
     * } $config
     */
    public function connect(array $config): ConnectionManager
    {
        $clock = $this->app[SystemClock::class];
        $connection = $this->app['db']->connection($config['connection']);
        $projectionProvider = new DatabaseProjectionProvider(
            $connection,
            $clock,
            $config['table_name'] ?? DatabaseProjectionProvider::TABLE_NAME
        );

        $eventStore = $this->projectorBridge->getEventStore($config['chronicler']);

        if (! $eventStore instanceof DatabaseChronicler) {
            throw ConfigurationViolation::message(sprintf(
                'Chronicler must be an instance of %s, got %s', DatabaseChronicler::class, get_class($eventStore)
            ));
        }

        $configSerializer = $this->app['config']->get('projector.serializer.'.$config['serializer']);

        return new DatabaseConnectionManager(
            $eventStore,
            $eventStore->getEventStreamProvider(),
            $projectionProvider,
            $this->app[$config['query_filter']],
            $clock,
            $this->serializerFactory->create($configSerializer),
            $this->app[$config['options']],
            ($config['dispatch_events'] ?? false) === true ? $this->app['events'] : null,
        );
    }
}
