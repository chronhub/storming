<?php

declare(strict_types=1);

namespace Storm\Reporter\Producer;

use Illuminate\Contracts\Bus\QueueingDispatcher;
use InvalidArgumentException;
use Storm\Contract\Serializer\MessageSerializer;
use Storm\Message\Message;

class IlluminateQueue
{
    public function __construct(
        protected QueueingDispatcher $dispatcher,
        protected MessageSerializer $messageSerializer
    ) {
    }

    public function toQueue(Message $message, array $currentQueue): void
    {
        if ($currentQueue === []) {
            throw new InvalidArgumentException('Queue cannot be empty for message name'.$message->name());
        }

        $payload = $this->messageSerializer->serializeMessage($message);

        $messageJob = new MessageJob($payload->jsonSerialize(), $currentQueue);

        $this->dispatcher->dispatchToQueue($messageJob);
    }
}
