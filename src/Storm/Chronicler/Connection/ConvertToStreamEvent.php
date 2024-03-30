<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connection;

use Storm\Contract\Chronicler\StreamEventConverter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Serializer\StreamEventSerializer;
use Storm\Stream\StreamName;

use function array_map;
use function is_iterable;
use function iterator_to_array;

final readonly class ConvertToStreamEvent implements StreamEventConverter
{
    public function __construct(private StreamEventSerializer $streamEventSerializer)
    {
    }

    public function toDomainEvent(object|iterable $streamEvents, StreamName $streamName): array|DomainEvent
    {
        if (is_iterable($streamEvents)) {
            return $this->deserializeEvents($streamEvents);
        }

        return $this->deserializeEvent($streamEvents);
    }

    private function deserializeEvent(object $streamEvent): DomainEvent
    {
        return $this->streamEventSerializer->deserialize($streamEvent);
    }

    /**
     * @return array<DomainEvent>
     */
    private function deserializeEvents(iterable $streamEvents): array
    {
        return array_map(
            fn (object $streamEvent) => $this->deserializeEvent($streamEvent),
            iterator_to_array($streamEvents)
        );
    }
}
