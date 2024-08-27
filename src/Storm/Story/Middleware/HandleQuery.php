<?php

declare(strict_types=1);

namespace Storm\Story\Middleware;

use React\Promise\Deferred;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\DomainQuery;
use Storm\Message\DomainCommand;
use Storm\Story\Draft;
use Storm\Story\Exception\StoryViolation;
use Throwable;

use function count;
use function get_class;

final class HandleQuery
{
    public function __invoke(Draft $draft, callable $next): Draft
    {
        [$query, $handler] = $this->checkQueryAndGetSingleHandler($draft);

        $deferred = new Deferred;

        try {
            $handler($query, $deferred);
        } catch (Throwable $exception) {
            $deferred->reject($exception);
        } finally {
            $draft->setPromise($deferred->promise());
        }

        $draft->markHandled();

        return $next($draft);
    }

    /**
     * Check the query and get the single handler.
     *
     * @return array{DomainQuery, callable}
     *
     * @throws StoryViolation when the query is a DomainCommand or DomainEvent instance
     * @throws StoryViolation when the query has not single handler
     */
    private function checkQueryAndGetSingleHandler(Draft $draft): array
    {
        $query = $draft->getMessage();
        if ($query instanceof DomainCommand || $query instanceof DomainEvent) {
            throw new StoryViolation('Expected a DomainQuery or object, got '.get_class($query));
        }

        $handlers = $draft->getOnceHandlers();
        if (count($handlers) !== 1) {
            throw new StoryViolation('Query only supports single handler only for query: '.get_class($query));
        }

        return [$query, $handlers[0]];
    }
}
