<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Illuminate\Contracts\Cache\Repository;
use Storm\Contract\Aggregate\AggregateCache;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Aggregate\AggregateRoot;

use function get_class;
use function sha1;
use function sprintf;

final readonly class DefaultAggregateCache implements AggregateCache
{
    public function __construct(
        private Repository $store,
        private string $cacheKeyPrefix,
        private int $cacheTtl,
    ) {}

    public function get(AggregateIdentity $aggregateIdentity): ?AggregateRoot
    {
        $cacheKey = $this->cacheKey($aggregateIdentity);

        if ($this->store->has($cacheKey)) {
            return $this->store->get($cacheKey);
        }

        return null;
    }

    public function put(AggregateRoot $aggregateRoot): void
    {
        $this->store->put($this->cacheKey($aggregateRoot->identity()), $aggregateRoot, $this->cacheTtl);
    }

    public function forget(AggregateIdentity $aggregateIdentity): void
    {
        $this->store->forget($this->cacheKey($aggregateIdentity));
    }

    public function has(AggregateIdentity $aggregateIdentity): bool
    {
        return $this->store->has($this->cacheKey($aggregateIdentity));
    }

    private function cacheKey(AggregateIdentity $aggregateIdentity): string
    {
        return sprintf(sha1('%s::%s::%s'),
            $this->cacheKeyPrefix,
            get_class($aggregateIdentity),
            $aggregateIdentity->toString()
        );
    }
}
