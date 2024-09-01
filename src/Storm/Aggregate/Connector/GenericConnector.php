<?php

declare(strict_types=1);

namespace Storm\Aggregate\Connector;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use Storm\Aggregate\AggregateEventReleaser;
use Storm\Contract\Clock\SystemClock;
use Storm\Stream\StreamName;

use function is_array;

final readonly class GenericConnector implements Connector
{
    public function __construct(
        private Container $container,
        private SystemClock $clock,
    ) {}

    public function connect(array $config): ConnectionManager
    {
        $eventStore = $this->container[$config['chronicler']];
        $streamName = new StreamName($config['stream_name']);

        $eventReleaser = new AggregateEventReleaser(
            $this->container[$config['event_decorator']],
        );

        $clock = $config['use_clock'] === true ? $this->clock : null;
        [$cacheStore, $cachePrefix, $cacheTtl] = $this->initializeCache($streamName, $config);

        return new GenericConnection(
            $eventStore,
            $streamName,
            $eventReleaser,
            $clock,
            $cacheStore,
            $cachePrefix,
            $cacheTtl,
        );
    }

    /**
     * Initializes the cache if it is enabled.
     *
     * @return array{?Repository, ?string, ?int<0, max>}
     */
    private function initializeCache(StreamName $streamName, array $config): array
    {
        $cacheConfig = $config['cache'] ?? false;

        if (! is_array($cacheConfig)) {
            return [null, null, null];
        }

        $store = $this->container['cache']->store($config['store'] ?? null);
        $cachePrefix = $cacheConfig['prefix'] ?? $streamName->name;
        $cacheTtl = $cacheConfig['ttl'] ?? 3600;

        return [$store, $cachePrefix, $cacheTtl];
    }
}
