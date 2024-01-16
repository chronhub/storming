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
        $queueMessage = $this->bindQueue($message);

        $payload = $this->messageSerializer->serializeMessage($queueMessage);

        $messageJob = new MessageJob($payload->jsonSerialize());

        $this->dispatcher->dispatchToQueue($messageJob);
    }

    protected function bindQueue(Message $message): Message
    {
        if ($message->hasNot(Header::QUEUE)) {
            $message = $message->withHeader(Header::QUEUE, 'default');
        }

        return $message;
    }
}
