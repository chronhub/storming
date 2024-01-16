<?php

declare(strict_types=1);

namespace Storm\Reporter\Producer;

use RuntimeException;
use Storm\Contract\Message\Header;
use Storm\Contract\Message\MessageProducer;
use Storm\Message\Message;

final readonly class AsyncMessageProducer implements MessageProducer
{
    public function __construct(private IlluminateQueue $queue)
    {
    }

    public function __invoke(Message $message): Message
    {
        if ($message->header(Header::EVENT_DISPATCHED) === true) {
            throw new RuntimeException('Message has already been dispatched');
        }

        $message = $message->withHeader(Header::EVENT_DISPATCHED, true);

        $this->queue->toQueue($message);

        return $message;
    }
}
