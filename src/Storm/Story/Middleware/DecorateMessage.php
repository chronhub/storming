<?php

declare(strict_types=1);

namespace Storm\Story\Middleware;

use Closure;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\Message;

final readonly class DecorateMessage
{
    private array $messageDecorators;

    public function __construct(MessageDecorator ...$messageDecorators)
    {
        $this->messageDecorators = $messageDecorators;
    }

    public function __invoke(Message $message, Closure $next): Message
    {
        foreach ($this->messageDecorators as $messageDecorator) {
            $message = $messageDecorator->decorate($message);
        }

        return $next($message);
    }
}
