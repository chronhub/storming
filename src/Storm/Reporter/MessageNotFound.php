<?php

declare(strict_types=1);

class MessageNotFound extends RuntimeException
{
    public static function withMessageName(string $messageName): self
    {
        throw new static("Message $messageName not found");
    }
}
