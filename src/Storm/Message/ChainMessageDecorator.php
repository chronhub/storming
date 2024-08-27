<?php

declare(strict_types=1);

namespace Storm\Message;

use Storm\Contract\Message\MessageDecorator;

final class ChainMessageDecorator implements MessageDecorator
{
    /** @var array<MessageDecorator>|array */
    private array $messageDecorators;

    public function __construct(MessageDecorator ...$messageDecorators)
    {
        $this->messageDecorators = $messageDecorators;
    }

    public function decorate(Message $message): Message
    {
        foreach ($this->messageDecorators as $messageDecorator) {
            $message = $messageDecorator->decorate($message);
        }

        return $message;
    }
}
