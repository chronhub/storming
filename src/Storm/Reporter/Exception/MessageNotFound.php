<?php

declare(strict_types=1);

namespace Storm\Reporter\Exception;

use RuntimeException;

class MessageNotFound extends RuntimeException
{
    public static function withMessageName(string $messageName): self
    {
        throw new static("Message $messageName not found");
    }
}
