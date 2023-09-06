<?php

declare(strict_types=1);

namespace Storm\Contract\Reporter;

use Storm\Message\Message;

interface MessageFilter
{
    public function allows(Message $message): bool;
}
