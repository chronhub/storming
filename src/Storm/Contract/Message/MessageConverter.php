<?php

declare(strict_types=1);

namespace Storm\Contract\Message;

use Storm\Message\Message;

interface MessageConverter
{
    public function convert(array|object $message): Message;
}
