<?php

declare(strict_types=1);

namespace Storm\Chronicler\Tracker;

use Storm\Chronicler\Direction;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Chronicler\EventableTransactionalChronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

final class ProvideEvents
{
    public static function withEvent(
        Chronicler $chronicler,
        StreamTracker $streamTracker,
        ?callable $publisherSubscriber = null
    ): void {

        if ($publisherSubscriber) {
            $streamTracker->subscribe(
                EventableChronicler::APPEND_STREAM,
                $publisherSubscriber,
                -100
            );
        }

        $streamTracker->subscribe(
            EventableChronicler::APPEND_STREAM,
            function (Stream $stream) use ($chronicler) {
                $chronicler->append($stream);

                return $stream;
            });

        $streamTracker->subscribe(
            EventableChronicler::DELETE_STREAM,
            function (StreamName $streamName) use ($chronicler) {
                $chronicler->delete($streamName);

                return $streamName;
            });

        $streamTracker->subscribe(
            EventableChronicler::RETRIEVE_ALL,
            function (StreamName $streamName, AggregateIdentity $aggregateId, Direction $direction) use ($chronicler) {
                return $chronicler->retrieveAll($streamName, $aggregateId, $direction);
            });

        $streamTracker->subscribe(
            EventableChronicler::RETRIEVE_ALL_REVERSED,
            function (StreamName $streamName, AggregateIdentity $aggregateId, Direction $direction) use ($chronicler) {
                return $chronicler->retrieveAll($streamName, $aggregateId, $direction);
            });

        $streamTracker->subscribe(
            EventableChronicler::RETRIEVE_FILTERED,
            function (StreamName $streamName, QueryFilter $queryFilter) use ($chronicler) {
                return $chronicler->retrieveFiltered($streamName, $queryFilter);
            });

        $streamTracker->subscribe(
            EventableChronicler::FILTER_STREAMS,
            function (string ...$streams) use ($chronicler) {
                return $chronicler->filterStreams(...$streams);
            });

        $streamTracker->subscribe(
            EventableChronicler::FILTER_PARTITIONS,
            function (string ...$partitions) use ($chronicler) {
                return $chronicler->filterPartitions(...$partitions);
            });

        $streamTracker->subscribe(
            EventableChronicler::STREAM_EXISTS,
            function (StreamName $streamName) use ($chronicler) {
                return $chronicler->hasStream($streamName);
            });
    }

    // todo transactional event store
    public static function withTransactionalEvent(EventableTransactionalChronicler $chronicler, StreamTracker $streamTracker): void
    {
        $streamTracker->subscribe(
            EventableTransactionalChronicler::BEGIN_TRANSACTION,
            function () use ($chronicler) {
                $chronicler->beginTransaction();
            });

        $streamTracker->subscribe(
            EventableTransactionalChronicler::COMMIT_TRANSACTION,
            function () use ($chronicler) {
                $chronicler->commitTransaction();
            });

        $streamTracker->subscribe(
            EventableTransactionalChronicler::ROLLBACK_TRANSACTION,
            function () use ($chronicler) {
                $chronicler->rollbackTransaction();
            });
    }
}
