<?php

declare(strict_types=1);

namespace Storm\Message\Decorator;

use Storm\Contract\Message\MessageDecorator;
use Storm\Message\Message;

final class NoOpMessageDecorator implements MessageDecorator
{
    public function decorate(Message $message): Message
    {
        return $message;
    }
}
