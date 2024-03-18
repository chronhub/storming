<?php

declare(strict_types=1);

namespace Storm\Serializer;

use InvalidArgumentException;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Contract\Serializer\ContentSerializer;
use Storm\Contract\Serializer\StreamEventSerializer;
use Symfony\Component\Serializer\Serializer;

use function is_string;
use function json_decode;

final readonly class DomainEventSerializer implements StreamEventSerializer
{
    public function __construct(
        private Serializer $serializer,
        private ContentSerializer $contentSerializer
    ) {
    }

    public function serializeEvent(DomainEvent $event): Payload
    {
        return new Payload(
            $this->contentSerializer->serialize($event),
            $this->serializer->normalize($event->headers(), 'json'),
        );
    }

    public function deserializePayload(Payload $payload): DomainEvent
    {
        $decodedPayload = $this->decodePayload($payload);

        $source = $decodedPayload->headers[Header::EVENT_TYPE] ?? null;

        if (is_string($source)) {
            $event = $this->contentSerializer->deserialize($source, $decodedPayload);

            return $event->withHeaders($decodedPayload->headers);
        }

        throw new InvalidArgumentException('Missing event type header string to deserialize payload');
    }

    public function encodePayload(array $payload): string
    {
        return $this->serializer->serialize($payload, 'json');
    }

    private function decodePayload(Payload $payload): Payload
    {
        $content = $payload->content;

        if (is_string($content)) {
            $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        }

        $headers = $payload->headers;

        if (is_string($headers)) {
            $headers = json_decode($headers, true, 512, JSON_THROW_ON_ERROR);
        }

        if (! isset($headers[EventHeader::INTERNAL_POSITION]) && $payload->seqNo !== null) {
            $headers[EventHeader::INTERNAL_POSITION] = $payload->seqNo;
        }

        return new Payload($content, $headers);
    }
}
