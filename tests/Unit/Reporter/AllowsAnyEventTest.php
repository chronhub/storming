<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use stdClass;
use Storm\Message\Message;
use Storm\Reporter\Filter\AllowsAnyEvent;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\Double\Message\SomeQuery;

it('allows', function (Message $message) {
    $filter = new AllowsAnyEvent();

    expect($filter->allows($message))->toBeTrue();
})->with([
    'object' => fn () => new Message(new stdClass()),
    'command' => new Message(new SomeEvent(['foo' => 'bar'])),
]);

it('deny', function (Message $message) {
    $filter = new AllowsAnyEvent();

    expect($filter->allows($message))->toBeFalse();
})->with([
    'event' => fn () => new Message(new SomeCommand(['foo' => 'bar'])),
    'query' => fn () => new Message(new SomeQuery(['foo' => 'bar'])),
]);
