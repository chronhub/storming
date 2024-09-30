<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Generator;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Aggregate\AggregateCache;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Aggregate\AggregateRepository;
use Storm\Contract\Aggregate\AggregateRoot;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Message\EventHeader;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;
use Throwable;

final readonly class DefaultAggregateRepository implements AggregateRepository
{
    public function __construct(
        private Chronicler $chronicler,
        private StreamName $streamName,
        private AggregateEventReleaser $eventReleaser,
        private AggregateCache $cache,
    ) {}

    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot
    {
        if ($this->cache->has($aggregateId)) {
            return $this->cache->get($aggregateId);
        }

        $aggregate = $this->reconstituteAggregate($aggregateId);

        if ($aggregate instanceof AggregateRoot) {
            $this->cache->put($aggregate);
        }

        return $aggregate;
    }

    public function retrieveFiltered(AggregateIdentity $aggregateId, QueryFilter $queryFilter): ?AggregateRoot
    {
        return $this->reconstituteAggregate($aggregateId, $queryFilter);
    }

    public function retrieveHistory(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter): Generator
    {
        if ($queryFilter instanceof QueryFilter) {
            return $this->chronicler->retrieveFiltered($this->streamName, $queryFilter);
        }

        return $this->chronicler->retrieveAll($this->streamName, $aggregateId);
    }

    public function store(AggregateRoot $aggregateRoot): void
    {
        $events = $this->eventReleaser->release($aggregateRoot);

        if ($events === []) {
            return;
        }

        try {
            $this->chronicler->append(new Stream($this->streamName, $events));
            $this->cache->put($aggregateRoot);
        } catch (Throwable $exception) {
            $this->cache->forget($aggregateRoot->identity());

            throw $exception;
        }
    }

    /**
     * Reconstitute aggregate from history.
     */
    private function reconstituteAggregate(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter = null): ?AggregateRoot
    {
        try {
            $history = $this->retrieveHistory($aggregateId, $queryFilter);

            if (! $history->valid()) {
                return null;
            }

            $firstEvent = $history->current();

            /** @var AggregateRoot $aggregateType */
            $aggregateType = $firstEvent->header(EventHeader::AGGREGATE_TYPE);

            return $aggregateType::reconstitute($aggregateId, $history);
        } catch (StreamNotFound) {
            return null;
        }
    }
}
