<?php

declare(strict_types=1);

namespace Storm\Projector\Connector;

use Illuminate\Contracts\Foundation\Application;
use Storm\Contract\Clock\SystemClock;

use function is_string;

final readonly class InMemoryConnector implements Connector
{
    public function __construct(
        private Application $app,
    ) {}

    public function connect(array $config): ConnectionManager
    {
        $options = $config['options'];

        if (is_string($options)) {
            $options = $this->app[$options];
        }

        $queryFilter = $config['query_filter'];

        if (is_string($queryFilter)) {
            $queryFilter = $this->app[$queryFilter];
        }

        return new InMemoryConnectionManager(
            $this->app[$config['chronicler']],
            $this->app[$config['chronicler.provider']],
            $this->app[$config['provider']],
            $queryFilter,
            $this->app[SystemClock::class],
            $this->app[$config['serializer']],
            $options,
            $config['dispatch_events'] === true ? $this->app['events'] : null,
        );
    }
}
