<?php

declare(strict_types=1);

namespace Storm\Story\Middleware;

use Storm\Message\DomainEvent;
use Storm\Story\Draft;
use Storm\Story\Exception\StoryViolation;

use function get_class;

final readonly class HandleEvent
{
    public function __invoke(Draft $draft, callable $next): Draft
    {
        $event = $draft->getMessage();

        if (! $event instanceof DomainEvent) {
            throw new StoryViolation('Expected a DomainEvent, got '.get_class($event));
        }

        $handlers = $draft->getOnceHandlers();

        foreach ($handlers as $handler) {
            $handler($event);
        }

        $draft->markHandled();

        return $next($draft);
    }
}
