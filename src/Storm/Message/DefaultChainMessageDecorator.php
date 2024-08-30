<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\Decorator\EventSymfonyId;
use Storm\Message\Decorator\EventTime;
use Storm\Message\Decorator\EventType;

final class DefaultChainMessageDecorator implements MessageDecorator
{
    /** @var array<MessageDecorator> */
    private array $chain = [];

    public function __construct(SystemClock $clock)
    {
        $this->chain[] = new EventSymfonyId;
        $this->chain[] = new EventTime($clock);
        $this->chain[] = new EventType;
    }

    public function decorate(Message $message): Message
    {
        foreach ($this->chain as $decorator) {
            $message = $decorator->decorate($message);
        }

        return $message;
    }
}
