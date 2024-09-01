<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Generator;
use Illuminate\Contracts\Cache\Repository;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Aggregate\AggregateRepository;
use Storm\Contract\Aggregate\AggregateRoot;
use Storm\Contract\Chronicler\QueryFilter;

use function get_class;
use function sha1;
use function sprintf;

final readonly class RepositoryCache implements AggregateRepository
{
    public function __construct(
        private AggregateRepository $aggregateRepository,
        private Repository $cache,
        private string $cachePrefix,
        private int $cacheTtl,
    ) {}

    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot
    {
        $cacheKey = $this->cacheKey($aggregateId);
        $aggregate = $this->cache->get($cacheKey);

        if ($aggregate instanceof AggregateRoot) {
            return $aggregate;
        }

        $aggregate = $this->aggregateRepository->retrieve($aggregateId);

        if ($aggregate instanceof AggregateRoot) {
            $this->cache->put($cacheKey, $aggregate, $this->cacheTtl);
        }

        return $aggregate;
    }

    public function store(AggregateRoot $aggregateRoot): void
    {
        $this->aggregateRepository->store($aggregateRoot);
    }

    public function retrieveFiltered(AggregateIdentity $aggregateId, QueryFilter $queryFilter): ?AggregateRoot
    {
        return $this->aggregateRepository->retrieveFiltered($aggregateId, $queryFilter);
    }

    public function retrieveHistory(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter): Generator
    {
        return $this->aggregateRepository->retrieveHistory($aggregateId, $queryFilter);
    }

    private function cacheKey(AggregateIdentity $aggregateId): string
    {
        return sprintf(sha1('%s::%s::%s'),
            $this->cachePrefix,
            get_class($aggregateId),
            $aggregateId->toString()
        );
    }
}
