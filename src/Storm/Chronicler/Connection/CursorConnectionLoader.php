<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connection;

use Generator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Serializer\StreamEventSerializer;
use Storm\Serializer\Payload;
use Storm\Stream\StreamName;

class CursorConnectionLoader
{
    public function __construct(protected StreamEventSerializer $streamEventSerializer)
    {
    }

    public function load(Builder $builder, StreamName $streamName): Generator
    {
        $streamEvents = $builder->cursor();

        yield from $this->deserializeEvents($streamEvents, $streamName);

        return $streamEvents->count();
    }

    /**
     * @return Generator<DomainEvent>
     *
     * @throws StreamNotFound      when the stream is not found
     * @throws NoStreamEventReturn when no events are returned
     */
    protected function deserializeEvents(iterable $streamEvents, StreamName $streamName): Generator
    {
        try {
            $count = 0;

            foreach ($streamEvents as $streamEvent) {
                $payload = new Payload(
                    $streamEvent->content,
                    $streamEvent->metadata,
                    $streamEvent->position,
                );

                yield $this->streamEventSerializer->deserializePayload($payload);

                $count++;
            }

            if ($count === 0) {
                throw NoStreamEventReturn::withStreamName($streamName);
            }

            return $count;
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '00000') {
                throw StreamNotFound::withStreamName($streamName);
            }
        }
    }
}
