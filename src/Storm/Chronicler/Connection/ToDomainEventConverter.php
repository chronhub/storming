<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connection;

use stdClass;
use Storm\Contract\Chronicler\StreamEventConverter;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Serializer\StreamEventSerializer;
use Storm\Serializer\Payload;
use Storm\Stream\StreamName;

use function is_iterable;

final readonly class ToDomainEventConverter implements StreamEventConverter
{
    public function __construct(private StreamEventSerializer $streamEventSerializer)
    {
    }

    public function toDomainEvent(object|iterable $streamEvents, StreamName $streamName): array|stdClass|DomainEvent
    {
        if (is_iterable($streamEvents)) {
            return $this->deserializeEvents($streamEvents);
        }

        return $this->deserializeStreamEvent($streamEvents);
    }

    private function deserializeStreamEvent(object $streamEvent): DomainEvent
    {
        return $this->streamEventSerializer->deserializePayload(
            new Payload(
                $streamEvent->content,
                $streamEvent->metadata,
                $streamEvent->position,
            )
        );
    }

    private function deserializeEvents(iterable $streamEvents): array
    {
        $tmp = [];
        foreach ($streamEvents as $streamEvent) {
            $tmp[] = $this->deserializeStreamEvent($streamEvent);
        }

        return $tmp;
    }
}
