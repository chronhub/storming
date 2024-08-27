<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\MessageFactory;
use Storm\Contract\Serializer\MessageSerializer;

use function is_array;

final readonly class GenericMessageFactory implements MessageFactory
{
    public function __construct(private MessageSerializer $messageSerializer) {}

    public function createMessageFrom(object|array $message): Message
    {
        if (is_array($message)) {
            $message = $this->messageSerializer->deserialize($message);
        }

        if ($message instanceof Message) {
            return new Message($message->event(), $message->headers());
        }

        return new Message($message);
    }
}
