<?php

declare(strict_types=1);

namespace Storm\Chronicler\Database;

use Generator;
use Illuminate\Database\Connection;
use Storm\Chronicler\Direction;
use Storm\Chronicler\Tracker\Listener;
use Storm\Chronicler\Tracker\ListenerOnce;
use Storm\Chronicler\Tracker\ProvideEvents;
use Storm\Chronicler\Tracker\StreamTracker;
use Storm\Contract\Aggregate\AggregateIdentity;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\DatabaseChronicler;
use Storm\Contract\Chronicler\EventableChronicler;
use Storm\Contract\Chronicler\EventableTransactionalChronicler;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Chronicler\TransactionalChronicler;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

final readonly class PublisherEventStore implements DatabaseChronicler, EventableTransactionalChronicler
{
    public function __construct(
        private TransactionalChronicler&DatabaseChronicler $chronicler,
        private StreamTracker $streamTracker,
        callable $publisherSubscriber
    ) {
        ProvideEvents::withEvent($this->chronicler, $streamTracker, $publisherSubscriber);
        ProvideEvents::withTransactionalEvent($this->chronicler, $this->streamTracker);
    }

    public function append(Stream $stream): void
    {
        $this->streamTracker->disclose(EventableChronicler::APPEND_STREAM, $stream);
    }

    public function delete(StreamName $streamName): void
    {
        $this->streamTracker->disclose(EventableChronicler::DELETE_STREAM, $streamName);
    }

    public function retrieveAll(StreamName $streamName, AggregateIdentity $aggregateId, Direction $direction = Direction::FORWARD): Generator
    {
        $eventName = $direction === Direction::FORWARD
            ? EventableChronicler::RETRIEVE_ALL
            : EventableChronicler::RETRIEVE_ALL_REVERSED;

        return $this->streamTracker->disclose($eventName, $streamName, $aggregateId, $direction);
    }

    public function retrieveFiltered(StreamName $streamName, QueryFilter $queryFilter): Generator
    {
        return $this->streamTracker->disclose(EventableChronicler::RETRIEVE_FILTERED, $streamName, $queryFilter);
    }

    public function filterStreams(string ...$streams): array
    {
        return $this->streamTracker->disclose(EventableChronicler::FILTER_STREAMS, ...$streams);
    }

    public function filterPartitions(string ...$partitions): array
    {
        return $this->streamTracker->disclose(EventableChronicler::FILTER_PARTITIONS, ...$partitions);
    }

    public function hasStream(StreamName $streamName): bool
    {
        return $this->streamTracker->disclose(EventableChronicler::STREAM_EXISTS, $streamName);
    }

    public function subscribe(string $eventName, callable $callback, int $priority = 0): Listener
    {
        return $this->streamTracker->subscribe($eventName, $callback, $priority);
    }

    public function subscribeOnce(string $eventName, callable $callback, int $priority = 0): ListenerOnce
    {
        return $this->streamTracker->subscribeOnce($eventName, $callback, $priority);
    }

    public function unsubscribe(Listener ...$listeners): void
    {
        foreach ($listeners as $listener) {
            $this->streamTracker->forget($listener);
        }
    }

    public function getEventStreamProvider(): EventStreamProvider
    {
        return $this->chronicler->getEventStreamProvider();
    }

    public function innerChronicler(): Chronicler
    {
        return $this->chronicler;
    }

    public function getConnection(): Connection
    {
        return $this->chronicler->getConnection();
    }

    public function beginTransaction(): void
    {
        $this->streamTracker->disclose(EventableTransactionalChronicler::BEGIN_TRANSACTION);
    }

    public function commitTransaction(): void
    {
        $this->streamTracker->disclose(EventableTransactionalChronicler::COMMIT_TRANSACTION);
    }

    public function rollbackTransaction(): void
    {
        $this->streamTracker->disclose(EventableTransactionalChronicler::ROLLBACK_TRANSACTION);
    }

    public function transactional(callable $callback): bool|array|string|int|float|object
    {
        return $this->chronicler->transactional($callback);
    }

    public function inTransaction(): bool
    {
        return $this->chronicler->inTransaction();
    }
}
