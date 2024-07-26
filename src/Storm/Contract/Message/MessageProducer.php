<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

use Storm\Message\Message;

interface MessageProducer
{
    public function __invoke(Message $message): Message;
}
