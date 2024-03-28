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
            $this->serializer->normalize($event->headers(), 'json'),
            $this->contentSerializer->serialize($event),
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
        // todo use serializer to decode
        $content = $payload->content;

        if (is_string($content)) {
            $content = $this->serializer->decode($content, 'json');
        }

        $headers = $payload->headers;

        if (is_string($headers)) {
            $headers = $this->serializer->decode($headers, 'json');
        }

        if (! isset($headers[EventHeader::INTERNAL_POSITION]) && $payload->seqNo !== null) {
            $headers[EventHeader::INTERNAL_POSITION] = $payload->seqNo;
        }

        return new Payload($headers, $content);
    }
}
