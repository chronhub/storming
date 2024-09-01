<?php

declare(strict_types=1);

namespace Storm\Aggregate\Connector;

use Illuminate\Contracts\Container\Container;
use Storm\Aggregate\AggregateEventReleaser;
use Storm\Aggregate\DefaultAggregateCache;
use Storm\Aggregate\NullAggregateCache;
use Storm\Contract\Aggregate\AggregateCache;
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
        $cache = $this->initializeCache($streamName, $config);

        return new GenericConnection(
            $eventStore,
            $streamName,
            $eventReleaser,
            $cache,
            $clock,
        );
    }

    /**
     * Initializes the cache if it is enabled.
     */
    private function initializeCache(StreamName $streamName, array $config): AggregateCache
    {
        $cacheConfig = $config['cache'] ?? false;

        if (! is_array($cacheConfig)) {
            return new NullAggregateCache;
        }

        $store = $this->container['cache']->store($config['store'] ?? null);
        $cachePrefix = $cacheConfig['prefix'] ?? $streamName->name;
        $cacheTtl = $cacheConfig['ttl'] ?? 3600;

        return new DefaultAggregateCache($store, $cachePrefix, $cacheTtl);
    }
}
