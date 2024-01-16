<?php

declare(strict_types=1);

namespace Storm\Support;

use React\Promise\PromiseInterface;

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
}
