<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use stdClass;
use Storm\Message\Message;
use Storm\Reporter\Filter\AllowsAll;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\Double\Message\SomeQuery;

it('always return true', function (object $message) {
    $filter = new AllowsAll();

    expect($filter->allows(new Message($message)))->toBeTrue();
})->with([
    'object' => fn () => new stdClass(),
    'command' => fn () => new SomeCommand(['foo' => 'bar']),
    'event' => fn () => new SomeEvent(['foo' => 'bar']),
    'query' => fn () => new SomeQuery(['foo' => 'bar']),
]);
