<?php

declare(strict_types=1);

namespace Storm\Message\Decorator;

use Storm\Contract\Message\Header;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\Message;

final readonly class EventType implements MessageDecorator
{
    public function decorate(Message $message): Message
    {
        if ($message->hasNot(Header::EVENT_TYPE)) {
            $message = $message->withHeader(Header::EVENT_TYPE, $message->type());
        }

        return $message;
    }
}
