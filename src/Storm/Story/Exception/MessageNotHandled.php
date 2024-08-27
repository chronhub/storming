<?php

declare(strict_types=1);

namespace Storm\Story\Exception;

use function get_class;

class MessageNotHandled extends StoryException
{
    public static function withMessage(object $message): self
    {
        return new self('Message not handled: '.get_class($message));
    }
}
