<?php

declare(strict_types=1);

namespace Storm\Reporter\Filter;

use Storm\Contract\Reporter\MessageFilter;
use Storm\Message\Message;

final class AllowsAll implements MessageFilter
{
    public function allows(Message $message): bool
    {
        return true;
    }
}
