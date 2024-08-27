<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

use Storm\Message\Message;

interface MessageDecorator
{
    public function decorate(Message $message): Message;
}
