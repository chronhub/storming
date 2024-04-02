<?php

declare(strict_types=1);

namespace Storm\Contract\Serializer;

use Storm\Contract\Message\Messaging;
use Storm\Message\Message;
use Storm\Serializer\Payload;

interface MessageSerializer
{
    /**
     * Serialize a message to a payload instance.
     * Meant to be used by a message producer.
     */
    public function serializeMessage(Message $message): Payload;

    /**
     * Deserialize data to a message instance.
     * Meant to be used by a message factory.
     *
     * By default, data accept an array or stdClass.
     */
    public function deserialize(mixed $data): Messaging;
}
