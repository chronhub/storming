<?php

declare(strict_types=1);

namespace Storm\Reporter\Producer;

use Illuminate\Contracts\Bus\QueueingDispatcher;
use Storm\Contract\Message\Header;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Message\Message;

class IlluminateQueue
{
    public function __construct(
        protected QueueingDispatcher $dispatcher,
        protected MessageSerializer $messageSerializer
    ) {
    }

    public function toQueue(Message $message): void
    {
        $payload = $this->messageSerializer->serializeMessage($message);

        $messageJob = new MessageJob($payload->jsonSerialize());

        $this->dispatcher->dispatchToQueue($messageJob);
    }
}
