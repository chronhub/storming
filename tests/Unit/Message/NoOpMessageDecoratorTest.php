<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use stdClass;
use Storm\Message\Decorator\NoOpMessageDecorator;
use Storm\Message\Message;
use Storm\Tests\Stubs\Double\Message\SomeCommand;

it('return same message instance', function (Message $message): void {

    $decorator = new NoOpMessageDecorator;

    expect($message)->toBe($decorator->decorate($message));
})->with([
    'content set' => fn (): Message => new Message(SomeCommand::fromContent(['foo' => 'bar'])),
    'headers set' => fn (): Message => new Message(new stdClass, ['some' => 'header']),
    'content and headers set' => fn (): Message => new Message(SomeCommand::fromContent(['foo' => 'bar']), ['another header']),
]);
