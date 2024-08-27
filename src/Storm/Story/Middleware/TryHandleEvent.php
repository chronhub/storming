<?php

declare(strict_types=1);

namespace Storm\Story\Middleware;

use Storm\Message\DomainEvent;
use Storm\Story\Draft;
use Storm\Story\Exception\CollectedEventHandlerError;
use Storm\Story\Exception\StoryViolation;
use Throwable;

use function get_class;

/**
 * Handle each event handler in a try/catch block.
 * If any handler throws an exception, the exception is collected,
 * and thrown as a single exception.
 */
final class TryHandleEvent
{
    /**
     * @throws StoryViolation             when the event is not a DomainEvent
     * @throws CollectedEventHandlerError when any handler throws an exception
     */
    public function __invoke(Draft $draft, callable $next): Draft
    {
        $event = $draft->getMessage();

        if (! $event instanceof DomainEvent) {
            throw new StoryViolation('Expected a DomainEvent, got '.get_class($event));
        }

        $handlers = $draft->getOnceHandlers();

        $exceptions = [];
        foreach ($handlers as $handler) {
            try {
                $handler($event);
            } catch (Throwable $exception) {
                $exceptions[] = $exception;
            }
        }

        if ($exceptions !== []) {
            throw CollectedEventHandlerError::fromExceptions(...$exceptions);
        }

        return $next($draft);
    }
}
