<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Illuminate\Contracts\Foundation\Application;
use Storm\Chronicler\EventStoreBridge;
use Storm\Contract\Chronicler\DatabaseChronicler;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Exception\ConfigurationViolation;
use Storm\Projector\Storage\DatabaseProjectionProvider;
use Storm\Serializer\SerializerFactory;

use function get_class;
use function sprintf;

final readonly class DatabaseConnector implements Connector
{
    public function __construct(
        private Application $app,
        private SerializerFactory $serializerFactory,
        private EventStoreBridge $projectorBridge,
        private SystemClock $clock,
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
        $eventStore = $this->getDatabaseEventStore($config);
        $projectionProvider = $this->getDatabaseProjectionProvider($config);
        $projectorSerializer = $this->getProjectorSerializer($config);

        return new DatabaseConnectionManager(
            $eventStore,
            $eventStore->getEventStreamProvider(),
            $projectionProvider,
            $this->app[$config['query_filter']],
            $this->clock,
            $projectorSerializer,
            $this->app[$config['options']],
            ($config['dispatch_events'] ?? false) === true ? $this->app['events'] : null,
        );
    }

    private function getDatabaseEventStore(array $config): DatabaseChronicler
    {
        $eventStore = $this->projectorBridge->getEventStore($config['chronicler']);

        if (! $eventStore instanceof DatabaseChronicler) {
            throw ConfigurationViolation::withMessage(sprintf(
                'Chronicler must be an instance of %s, got %s', DatabaseChronicler::class, get_class($eventStore)
            ));
        }

        return $eventStore;
    }

    private function getDatabaseProjectionProvider(array $config): ProjectionProvider
    {
        $connection = $this->app['db']->connection($config['connection']);

        return new DatabaseProjectionProvider(
            $connection,
            $this->clock,
            $config['table_name'] ?? DatabaseProjectionProvider::TABLE_NAME
        );
    }

    private function getProjectorSerializer(array $config): SymfonySerializer
    {
        $configSerializer = $this->app['config']->get('projector.serializer.'.$config['serializer']);

        return $this->serializerFactory->create($configSerializer);
    }
}
