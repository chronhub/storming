<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Generator;
use Illuminate\Contracts\Cache\Repository;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Aggregate\AggregateRepository;
use Storm\Contract\Aggregate\AggregateRoot;
use Storm\Contract\Chronicler\QueryFilter;
use Throwable;

use function get_class;
use function sprintf;

final readonly class GenericAggregateRepositoryCache implements AggregateRepository
{
    public function __construct(
        private AggregateRepository $aggregateRepository,
        private Repository $cache,
        private string $cachePrefix = 'aggregate',
        private int $cacheTtl = 3600,
    ) {}

    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot
    {
        $cacheKey = $this->cacheKey($aggregateId);
        $aggregate = $this->cache->get($cacheKey);

        if ($aggregate instanceof AggregateRoot) {
            return $aggregate;
        }

        return $this->aggregateRepository->retrieve($aggregateId);
    }

    public function store(AggregateRoot $aggregateRoot): void
    {
        $cacheKey = $this->cacheKey($aggregateRoot->identity());

        try {
            $this->aggregateRepository->store($aggregateRoot);
            $this->cache->put($cacheKey, $aggregateRoot, $this->cacheTtl);
        } catch (Throwable $exception) {
            $this->cache->forget($cacheKey);

            throw $exception;
        }
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
        return sprintf('%s::%s::%s',
            $this->cachePrefix,
            get_class($aggregateId),
            $aggregateId->toString()
        );
    }
}
