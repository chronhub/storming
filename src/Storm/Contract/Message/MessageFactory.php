<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

use Storm\Message\Message;

interface MessageFactory
{
    /**
     * Create a new message instance from the given payload.
     */
    public function createMessageFrom(object|array $message): Message;
}
