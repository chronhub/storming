<?php

declare(strict_types=1);

namespace Storm\Reporter\Filter;

use Storm\Contract\Message\DomainCommand;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\DomainQuery;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Message\Message;

final class AllowsAnyEvent implements MessageFilter
{
    public function allows(Message $message): bool
    {
        $messageEvent = $message->event();

        if ($messageEvent instanceof DomainEvent) {
            return true;
        }

        return ! $messageEvent instanceof DomainCommand && ! $messageEvent instanceof DomainQuery;
    }
}
