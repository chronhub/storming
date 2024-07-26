<?php

declare(strict_types=1);

namespace Storm\Message\Decorator;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\Header;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\Message;

final readonly class EventTime implements MessageDecorator
{
    public function __construct(private SystemClock $clock) {}

    public function decorate(Message $message): Message
    {
        if ($message->hasNot(Header::EVENT_TIME)) {
            $message = $message->withHeader(Header::EVENT_TIME, $this->clock->generate());
        }

        return $message;
    }
}
