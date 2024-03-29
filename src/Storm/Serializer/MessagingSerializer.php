<?php

declare(strict_types=1);

namespace Storm\Serializer;

use InvalidArgumentException;
use Storm\Contract\Message\Header;
use Storm\Contract\Message\Messaging;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Message\Message;
use Symfony\Component\Serializer\Serializer;

use function is_array;

// fixMe naming as its more a normalization than serialization
final readonly class MessagingSerializer implements MessageSerializer
{
    public function __construct(private Serializer $serializer)
    {
    }

    public function serializeMessage(Message $message): Payload
    {
        return new Payload(
            $this->serializer->normalize($message->headers(), 'json'),
            $this->serializer->serialize($message->event()->toContent(), 'json')
        );
    }

    public function deserialize(mixed $data): Messaging
    {
        if (! is_array($data)) {
            throw new InvalidArgumentException('Data must be an array');
        }

        $payload = $this->serializer->denormalize($data, Payload::class, 'json');

        $event = $payload->header[Header::EVENT_TYPE];

        return $this->serializer->denormalize($payload->jsonSerialize(), $event, 'json');
    }
}
