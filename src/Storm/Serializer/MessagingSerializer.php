<?php

declare(strict_types=1);

namespace Storm\Serializer;

use Storm\Contract\Message\Header;
use Storm\Contract\Message\Messaging;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Message\Message;

final readonly class MessagingSerializer implements MessageSerializer
{
    public function __construct(private SymfonySerializer $serializer) {}

    public function serializeMessage(Message $message): Payload
    {
        return new Payload(
            $this->serializer->normalize($message->headers(), 'json'),
            $this->serializer->serialize($message->event()->toContent(), 'json')
        );
    }

    public function deserialize(mixed $data): Messaging
    {
        $payload = $this->serializer->denormalize($data, Payload::class, 'json');

        return $this->serializer->denormalize(
            $payload->jsonSerialize(),
            $payload->header[Header::EVENT_TYPE],
            'json'
        );
    }
}
