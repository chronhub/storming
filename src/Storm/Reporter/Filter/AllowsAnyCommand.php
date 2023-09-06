<?php

declare(strict_types=1);

namespace Storm\Reporter\Filter;

use Storm\Contract\Message\DomainCommand;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\DomainQuery;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Message\Message;

/**
 * Dummy filter which allow Domain command and naked object
 */
final class AllowsAnyCommand implements MessageFilter
{
    public function allows(Message $message): bool
    {
        $messageEvent = $message->event();

        if ($messageEvent instanceof DomainCommand) {
            return true;
        }

        return ! $messageEvent instanceof DomainEvent && ! $messageEvent instanceof DomainQuery;
    }
}
