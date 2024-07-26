<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\MessageDecorator;

final class NoOpMessageDecorator implements MessageDecorator
{
    public function decorate(Message $message): Message
    {
        return $message;
    }
}
