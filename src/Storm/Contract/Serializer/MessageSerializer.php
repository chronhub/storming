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
     */
    public function serializeMessage(Message $message): Payload;

    /**
     * Deserialize data to a message instance.
     *
     * By default, data accept an array or stdClass.
     */
    public function deserialize(mixed $data): Messaging;
}
