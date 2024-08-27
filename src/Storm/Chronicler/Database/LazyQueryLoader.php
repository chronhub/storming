<?php

declare(strict_types=1);

namespace Storm\Chronicler\Database;

use Generator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\LazyCollection;
use PDO;
use Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\DatabaseQueryLoader;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Serializer\StreamEventSerializer;
use Storm\Stream\StreamName;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final readonly class LazyQueryLoader implements DatabaseQueryLoader
{
    public function __construct(private StreamEventSerializer $serializer) {}

    public function load(Builder $builder, StreamName $streamName): Generator
    {
        try {
            $streamEvents = $builder->cursor();

            return $this->deserializeEvents($streamEvents, $streamName);
        } catch (QueryException $queryException) {
            match ($queryException->getCode()) {
                PDO::ERR_NONE => throw NoStreamEventReturn::withStreamName($streamName),
                default => throw StreamNotFound::withStreamName($streamName, $queryException),
            };
        }
    }

    /**
     * Deserialize stream events.
     *
     * @return Generator<DomainEvent>
     *
     * @throws ExceptionInterface  when an error occurs during deserialization
     * @throws NoStreamEventReturn when the stream is not found
     */
    private function deserializeEvents(LazyCollection $streamEvents, StreamName $streamName): Generator
    {
        if ($streamEvents->isEmpty()) {
            throw NoStreamEventReturn::withStreamName($streamName);
        }

        foreach ($streamEvents as $streamEvent) {
            yield $this->serializer->deserialize($streamEvent);
        }

        return $streamEvents->count();
    }
}
