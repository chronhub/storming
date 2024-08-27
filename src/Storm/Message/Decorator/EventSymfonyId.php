<?php

declare(strict_types=1);

namespace Storm\Message\Decorator;

use Storm\Contract\Message\Header;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\Message;
use Symfony\Component\Uid\Uuid;

final class EventSymfonyId implements MessageDecorator
{
    public function decorate(Message $message): Message
    {
        if ($message->hasNot(Header::EVENT_ID)) {
            $message = $message->withHeader(Header::EVENT_ID, Uuid::v4()->jsonSerialize());
        }

        return $message;
    }
}
