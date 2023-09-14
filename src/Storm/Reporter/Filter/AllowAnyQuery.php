<?php

declare(strict_types=1);

namespace Storm\Reporter\Filter;

use Storm\Contract\Message\DomainCommand;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Message\Message;

final class AllowAnyQuery implements MessageFilter
{
    public function allows(Message $message): bool
    {
        $messageEvent = $message->event();

        return match (true) {
            $messageEvent instanceof DomainEvent, $messageEvent instanceof DomainCommand => false,
            default => true,
        };
    }
}
