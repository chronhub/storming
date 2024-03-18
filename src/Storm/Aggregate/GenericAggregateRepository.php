<?php

declare(strict_types=1);

namespace Storm\Aggregate;

use Generator;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Aggregate\AggregateRepository;
use Storm\Contract\Aggregate\AggregateRoot;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Message\EventHeader;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

final readonly class GenericAggregateRepository implements AggregateRepository
{
    public function __construct(
        protected Chronicler $chronicler,
        protected StreamName $streamName,
        protected AggregateEventReleaser $eventReleaser,
    ) {
    }

    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot
    {
        return $this->reconstituteAggregate($aggregateId);
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

        $this->chronicler->append(new Stream($this->streamName, $events));
    }

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
