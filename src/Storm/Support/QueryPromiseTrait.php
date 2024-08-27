<?php

declare(strict_types=1);

namespace Storm\Support;

use React\Promise\PromiseInterface;

use function React\Promise\all;

trait QueryPromiseTrait
{
    public function handlePromise(PromiseInterface $promise, bool $raiseException = true): mixed
    {
        $result = null;
        $exception = null;

        $promise->then(
            function ($value) use (&$result) {
                $result = $value;
            },
            function ($reason) use (&$exception) {
                $exception = $reason;
            }
        );

        if ($exception && $raiseException) {
            throw $exception;
        }

        return $result;
    }

    public function handleQueries(array $promises, bool $raiseException = true): ?array
    {
        $result = null;
        $exceptions = null;

        all($promises)->then(
            function ($value) use (&$result) {
                $result = $value;
            },
            function ($reason) use (&$exceptions) {
                $exceptions = $reason;
            }
        );

        if ($exceptions !== [] && $raiseException) {
            throw $exceptions;
        }

        return $result ?? $exceptions;
    }
}
