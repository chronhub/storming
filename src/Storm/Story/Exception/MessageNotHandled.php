<?php

declare(strict_types=1);

namespace Storm\Story\Exception;

use function get_class;

class MessageNotHandled extends StoryException
{
    protected static ?object $unhandledMessage = null;

    public static function withMessage(object $message): self
    {
        self::$unhandledMessage = $message;

        return new self('Message not handled: '.get_class($message));
    }

    public function getUnhandledMessage(): ?object
    {
        return self::$unhandledMessage;
    }
}
