<?php

declare(strict_types=1);

use React\Promise\PromiseInterface;

/**
 * @throws ReflectionException
 */
function getPrivateProperty(object $object, string $property): mixed
{
    $reflectedClass = new ReflectionClass($object);
    $reflection = $reflectedClass->getProperty($property);

    return $reflection->getValue($object);
}

function getPromiseResult(PromiseInterface $promise): mixed
{
    $result = null;
    $exception = null;

    $promise
        ->then(function ($r) use (&$result): void {
            $result = $r;
        }, function ($e) use (&$exception): void {
            $exception = $e;
        });

    return $exception ?? $result;
}
