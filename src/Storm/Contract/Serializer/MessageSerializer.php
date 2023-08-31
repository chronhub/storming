<?php

declare(strict_types=1);

namespace Storm\Contract\Serializer;

use Storm\Contract\Message\Messaging;
use Storm\Message\Message;
use Storm\Serializer\Payload;

interface MessageSerializer
{
    public function serializeMessage(Message $message): Payload;

    public function deserializePayload(Payload $payload): Messaging;
}
