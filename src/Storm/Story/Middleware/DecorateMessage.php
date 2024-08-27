<?php

declare(strict_types=1);

namespace Storm\Story\Middleware;

use Closure;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\Message;

final readonly class DecorateMessage
{
    public function __construct(private MessageDecorator $messageDecorator) {}

    public function __invoke(Message $message, Closure $next): Message
    {
        $message = $this->messageDecorator->decorate($message);

        return $next($message);
    }
}
