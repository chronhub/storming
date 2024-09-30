<?php

declare(strict_types=1);

namespace Storm\Chronicler\Factory\Pgsql;

use Illuminate\Contracts\Foundation\Application;
use Storm\Chronicler\Exceptions\ConfigurationViolation;
use Storm\Chronicler\Factory\ConnectionManager;
use Storm\Chronicler\Factory\Connector;
use Storm\Contract\Chronicler\DatabaseChronicler;

final readonly class TransactionalConnector implements Connector
{
    public function __construct(private Application $app) {}

    public function connect(array $config): ConnectionManager
    {
        $connector = $this->app[PgsqlConnector::class];

        $manager = $connector->connect($config);
        $eventStore = $manager->create();

        if (! $eventStore instanceof DatabaseChronicler) {
            throw new ConfigurationViolation('The event store must be a database chronicler');
        }

        return new TransactionalConnection($eventStore);
    }
}
