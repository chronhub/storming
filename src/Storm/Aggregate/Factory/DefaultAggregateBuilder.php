<?php

declare(strict_types=1);

namespace Storm\Aggregate\Factory;

use Storm\Aggregate\AggregateEventReleaser;
use Storm\Aggregate\DefaultAggregateRepository;
use Storm\Contract\Aggregate\AggregateBuilder;
use Storm\Contract\Aggregate\AggregateCache;
use Storm\Contract\Aggregate\AggregateRepository;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Stream\StreamName;

final readonly class DefaultAggregateBuilder implements AggregateBuilder
{
    public function __construct(
        private Chronicler $chronicler,
        private StreamName $streamName,
        private AggregateEventReleaser $releaser,
        private AggregateCache $cache,
    ) {}

    public function create(): AggregateRepository
    {
        return new DefaultAggregateRepository(
            $this->chronicler,
            $this->streamName,
            $this->releaser,
            $this->cache,
        );
    }
}
