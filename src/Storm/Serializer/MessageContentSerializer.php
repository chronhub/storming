<?php

declare(strict_types=1);

namespace Storm\Serializer;

use InvalidArgumentException;
use Storm\Contract\Message\Messaging;
use Storm\Contract\Serializer\ContentSerializer;

use function is_a;

final class MessageContentSerializer implements ContentSerializer
{
    public function serialize(Messaging $messaging): array
    {
        return $messaging->toContent();
    }

    public function deserialize(string $source, Payload $payload): object
    {
        if (is_a($source, Messaging::class, true)) {
            return $source::fromContent($payload->content);
        }

        throw new InvalidArgumentException('Only class which implement Messaging contract can be deserialized');
    }
}
