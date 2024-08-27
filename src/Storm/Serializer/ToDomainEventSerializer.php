<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\Header;
use Storm\Contract\Serializer\StreamEventSerializer;
use Storm\Contract\Serializer\SymfonySerializer;

final readonly class ToDomainEventSerializer implements StreamEventSerializer
{
    public function __construct(private SymfonySerializer $serializer) {}

    public function serialize(DomainEvent $event): Payload
    {
        $headers = $this->serializer->normalize($event->headers(), 'json');
        $content = $this->serializer->serialize($event->toContent(), 'json');

        return new Payload($headers, $content);
    }

    public function deserialize(array|object $object): DomainEvent
    {
        $payload = $this->serializer->denormalize($object, Payload::class, 'json');

        return $this->serializer->denormalize(
            $payload->jsonSerialize(),
            $payload->header[Header::EVENT_TYPE],
            'json'
        );
    }
}
