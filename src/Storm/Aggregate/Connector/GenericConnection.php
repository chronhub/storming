<?php

declare(strict_types=1);

namespace Storm\Aggregate\Connector;

use Illuminate\Contracts\Cache\Repository;
use Storm\Aggregate\AggregateEventReleaser;
use Storm\Aggregate\DefaultAggregateRepository;
use Storm\Aggregate\RepositoryCache;
use Storm\Contract\Aggregate\AggregateRepository;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Clock\SystemClock;
use Storm\Stream\StreamName;

final readonly class GenericConnection implements ConnectionManager
{
    public function __construct(
        private Chronicler $chronicler,
        private StreamName $streamName,
        private AggregateEventReleaser $releaser,
        private ?SystemClock $clock = null,
        private ?Repository $store = null,
        private ?string $cachePrefix = null,
        private ?int $cacheTtl = null,
    ) {}

    public function create(): AggregateRepository
    {
        $repository = new DefaultAggregateRepository(
            $this->chronicler,
            $this->streamName,
            $this->releaser,
            $this->clock,
        );

        if ($this->store === null) {
            return $repository;
        }

        return new RepositoryCache(
            $repository,
            $this->store,
            $this->cachePrefix,
            $this->cacheTtl,
        );
    }
}
