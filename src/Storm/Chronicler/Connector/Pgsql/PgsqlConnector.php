<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connector\Pgsql;

use Illuminate\Contracts\Foundation\Application;
use Storm\Chronicler\Connector\ConnectionManager;
use Storm\Chronicler\Connector\Connector;
use Storm\Chronicler\Database\LazyQueryLoader;
use Storm\Chronicler\Database\StandardStreamPersistence;
use Storm\Chronicler\Exceptions\ConfigurationViolation;
use Storm\Contract\Chronicler\DatabaseQueryLoader;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Serializer\SerializerFactory;
use Storm\Serializer\ToDomainEventSerializer;

use function is_string;

final readonly class PgsqlConnector implements Connector
{
    public function __construct(
        private Application $app,
        private SerializerFactory $serializerFactory,
    ) {}

    public function connect(array $config): ConnectionManager
    {
        if ($config['connection'] !== 'pgsql') {
            throw new ConfigurationViolation('Only pgsql connection is supported');
        }

        $connection = $this->app['db']->connection('pgsql');
        $serializer = $this->createSerializer($config);
        $streamEventLoader = $this->createStreamEventLoader($serializer, $config);

        return new PgsqlConnection(
            $connection,
            $this->createEventStreamProvider($config),
            new StandardStreamPersistence($serializer),
            $streamEventLoader,
            $config['table_name'] ?? null,
        );
    }

    private function createEventStreamProvider(array $config): EventStreamProvider
    {
        $key = $config['provider'];

        $provider = $this->app['config']->get('chronicler.provider.connection.'.$key);

        if (! is_string($provider)) {
            throw new ConfigurationViolation("Invalid provider key $key configuration");
        }

        return $this->app[$provider];
    }

    private function createStreamEventLoader(SymfonySerializer $serializer, array $config): DatabaseQueryLoader
    {
        $loader = $config['loader'] ?? null;

        return is_string($loader)
            ? $this->app[$loader]
            : new LazyQueryLoader(new ToDomainEventSerializer($serializer));
    }

    private function createSerializer(array $config): SymfonySerializer
    {
        $key = $config['serializer'];

        if (! is_string($key)) {
            throw new ConfigurationViolation('Invalid serializer configuration in connector '.self::class);
        }

        $serializer = $this->app['config']->get("chronicler.serializer.$key");

        if (blank($serializer)) {
            throw new ConfigurationViolation("Invalid serializer configuration key [$key] in connector ".self::class);
        }

        return $this->serializerFactory->create($serializer);
    }
}
