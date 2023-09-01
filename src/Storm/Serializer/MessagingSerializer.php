<?php

declare(strict_types=1);

namespace Storm\Serializer;

use InvalidArgumentException;
use Storm\Contract\Message\Header;
use Storm\Contract\Message\Messaging;
use Storm\Contract\Serializer\ContentSerializer;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Message\Message;
use Symfony\Component\Serializer\Serializer;

use function is_string;

final readonly class MessagingSerializer implements MessageSerializer
{
    public function __construct(
        private Serializer $serializer,
        private ContentSerializer $contentSerializer)
    {
    }

    public function serializeMessage(Message $message): Payload
    {
        return new Payload(
            $this->contentSerializer->serialize($message->event()),
            $this->serializer->normalize($message->headers(), 'json'),
        );
    }

    public function deserializePayload(Payload $payload): Messaging
    {
        $decodedPayload = $this->decodePayload($payload);

        $source = $decodedPayload->headers[Header::EVENT_TYPE] ?? null;

        if (is_string($source)) {
            $event = $this->contentSerializer->deserialize($source, $decodedPayload);

            return $event->withHeaders($decodedPayload->headers);
        }

        throw new InvalidArgumentException('Missing event type header string to deserialize payload');
    }

    private function decodePayload(Payload $payload): Payload
    {
        $content = $payload->content;

        if (is_string($content)) {
            $content = $this->serializer->decode($content, 'json');
        }

        $headers = $payload->headers;

        if (is_string($headers)) {
            $headers = $this->serializer->decode($headers, 'json');
        }

        return new Payload($content, $headers);
    }
}
