<?php

declare(strict_types=1);

namespace Storm\Chronicler\Connection;

use Storm\Contract\Chronicler\StreamPersistence;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Serializer\StreamEventSerializer;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

use function array_map;
use function iterator_to_array;

final readonly class StandardStreamPersistence implements StreamPersistence
{
    public function __construct(
        protected StreamEventSerializer $streamEventSerializer,
        protected SystemClock $clock
    ) {
    }

    public function serialize(Stream $stream): array
    {
        $streamName = $stream->name();

        return array_map(
            fn (DomainEvent $event) => $this->serializeEvent($event, $streamName),
            iterator_to_array($stream->events())
        );
    }

    protected function serializeEvent(DomainEvent $event, StreamName $streamName): array
    {
        $payload = $this->streamEventSerializer->serializeEvent($event);

        return [
            'stream_name' => $streamName->name,
            'type' => $payload->headers[EventHeader::AGGREGATE_TYPE],
            'id' => $payload->headers[EventHeader::AGGREGATE_ID],
            'version' => $payload->headers[EventHeader::AGGREGATE_VERSION],
            'metadata' => $this->streamEventSerializer->encodePayload($payload->headers),
            'content' => $this->streamEventSerializer->encodePayload($payload->content),
            'created_at' => $this->clock->generate(),
        ];
    }
}
