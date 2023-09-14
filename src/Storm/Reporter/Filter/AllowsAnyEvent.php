<?php

declare(strict_types=1);

namespace Storm\Reporter\Filter;

use Storm\Contract\Message\DomainCommand;
use Storm\Contract\Message\DomainQuery;
use Storm\Contract\Reporter\MessageFilter;
use Storm\Message\Message;

final class AllowsAnyEvent implements MessageFilter
{
    public function allows(Message $message): bool
    {
        $messageEvent = $message->event();

        return match (true) {
            $messageEvent instanceof DomainQuery, $messageEvent instanceof DomainCommand => false,
            default => true,
        };
    }
}
