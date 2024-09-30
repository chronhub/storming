<?php

declare(strict_types=1);

namespace Storm\Aggregate\Factory;

use Illuminate\Contracts\Container\Container;
use Storm\Aggregate\AggregateEventReleaser;
use Storm\Aggregate\Cache\DefaultAggregateCache;
use Storm\Aggregate\Cache\NullAggregateCache;
use Storm\Contract\Aggregate\AggregateBuilder;
use Storm\Contract\Aggregate\AggregateCache;
use Storm\Contract\Aggregate\AggregateFactory;
use Storm\Stream\StreamName;

use function is_array;

final readonly class DefaultAggregateFactory implements AggregateFactory
{
    public function __construct(
        private Container $container,
    ) {}

    public function make(array $config): AggregateBuilder
    {
        $eventStore = $this->container[$config['chronicler']];
        $streamName = new StreamName($config['stream_name']);

        $eventReleaser = new AggregateEventReleaser(
            $this->container[$config['event_decorator']],
        );

        $cache = $this->initializeCache($streamName, $config);

        return new DefaultAggregateBuilder(
            $eventStore,
            $streamName,
            $eventReleaser,
            $cache,
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

        // todo: set max cache size but need cluster support or use tags

        return new DefaultAggregateCache($store, $cachePrefix, $cacheTtl);
    }
}
