<?php

declare(strict_types=1);

namespace Storm\Story\Exception;

class MessageNotFound extends StoryException
{
    public static function forMessage(string $name): self
    {
        throw new static("Message $name not found");
    }
}
