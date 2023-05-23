<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

use Storm\Message\Message;

interface MessageFactory
{
    public function createMessageFrom(object|array $message): Message;
}
