<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connection;

use Storm\Contract\Chronicler\StreamEventConverter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Serializer\StreamEventSerializer;
use Storm\Serializer\Payload;

use function array_map;
use function is_iterable;
use function iterator_to_array;

final readonly class ToDomainEventConverter implements StreamEventConverter
{
    public function __construct(private StreamEventSerializer $streamEventSerializer)
    {
    }

    public function toDomainEvent(object|iterable $streamEvents): array|DomainEvent
    {
        if (is_iterable($streamEvents)) {
            return $this->deserializeEvents($streamEvents);
        }

        return $this->deserializeEvent($streamEvents);
    }

    private function deserializeEvent(object $streamEvent): DomainEvent
    {
        // fixMe: metadata to header
        return $this->streamEventSerializer->deserializePayload(
            new Payload($streamEvent->content, $streamEvent->metadata, $streamEvent->position)
        );
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
