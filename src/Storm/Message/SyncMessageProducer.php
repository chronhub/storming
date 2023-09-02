<?php

declare(strict_types=1);

namespace Storm\Message;

use RuntimeException;
use Storm\Contract\Message\Header;
use Storm\Contract\Message\MessageProducer;

final class SyncMessageProducer implements MessageProducer
{
    public function __invoke(Message $message): Message
    {
        if ($message->header(Header::EVENT_DISPATCHED) === true) {
            throw new RuntimeException('Message event has already been dispatched');
        }

        return $message->withHeader(Header::EVENT_DISPATCHED, true);
    }
}
