<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use InvalidArgumentException;
use RuntimeException;
use Storm\Reporter\Exception\CollectedEventHandlerError;

it('assert instance', function () {
    $exceptions = [
        new InvalidArgumentException('foo'),
        new RuntimeException('bar'),
    ];

    $exception = CollectedEventHandlerError::fromExceptions(...$exceptions);

    $expectedMessage = 'One or many event handler(s) cause exception'.PHP_EOL;
    $expectedMessage .= $exceptions[0]->getMessage().PHP_EOL;
    $expectedMessage .= $exceptions[1]->getMessage().PHP_EOL;

    expect($exception->getEventExceptions())->toBe($exceptions)
        ->and($exception->getMessage())->toBe($expectedMessage);
});
