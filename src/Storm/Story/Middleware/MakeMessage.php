<?php

declare(strict_types=1);

namespace Storm\Story\Middleware;

use Closure;
use Storm\Contract\Message\MessageFactory;
use Storm\Message\Message;

/**
 * @deprecated
 */
final readonly class MakeMessage
{
    public function __construct(private MessageFactory $messageFactory) {}

    public function __invoke(array|object $payload, Closure $next): Message
    {
        $message = $this->messageFactory->createMessageFrom($payload);

        return $next($message);
    }
}
