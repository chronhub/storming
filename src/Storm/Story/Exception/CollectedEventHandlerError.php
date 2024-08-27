<?php

declare(strict_types=1);

namespace Storm\Story\Exception;

use Throwable;

class CollectedEventHandlerError extends StoryException
{
    private array $exceptions = [];

    public static function fromExceptions(Throwable ...$exceptions): self
    {
        $message = "One or many event handler(s) cause exception\n";

        foreach ($exceptions as $exception) {
            $message .= $exception->getMessage().PHP_EOL;
        }

        $self = new self($message);

        $self->exceptions = $exceptions;

        return $self;
    }

    public function getEventExceptions(): array
    {
        return $this->exceptions;
    }
}
