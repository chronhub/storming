<?php

declare(strict_types=1);

namespace Storm\Reporter\Filter;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\DomainQuery;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Message\Message;

final class AllowsAnyCommand implements MessageFilter
{
    public function allows(Message $message): bool
    {
        $messageEvent = $message->event();

        return match (true) {
            $messageEvent instanceof DomainEvent, $messageEvent instanceof DomainQuery => false,
            default => true,
        };
    }
}
