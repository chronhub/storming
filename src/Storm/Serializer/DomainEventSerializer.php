<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\Header;
use Storm\Contract\Serializer\StreamEventSerializer;
use Symfony\Component\Serializer\Serializer;

final readonly class DomainEventSerializer implements StreamEventSerializer
{
    public function __construct(private Serializer $serializer)
    {
    }

    public function serialize(DomainEvent $event): Payload
    {
        return new Payload(
            $this->serializer->normalize($event->headers(), 'json'),
            $this->serializer->serialize($event->toContent(), 'json')
        );
    }

    public function deserialize(object $object): DomainEvent
    {
        $payload = $this->serializer->denormalize($object, Payload::class, 'json');

        $event = $payload->header[Header::EVENT_TYPE];

        return $this->serializer->denormalize($payload->jsonSerialize(), $event, 'json');
    }

    public function toStreamEvent(mixed $data): Payload
    {
        return $this->serializer->denormalize($data, Payload::class, 'json');
    }
}
