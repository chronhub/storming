<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Message;

use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Storm\Contract\Message\Messaging;
use Storm\Message\Message;
use Storm\Tests\Stubs\Double\Message\SomeCommand;

it('create message instance with object event', function () {
    $event = new stdClass();
    $message = new Message($event, []);

    expect($message)->toBeInstanceOf(Message::class)
        ->and($event)->not()->toBe($message->event())
        ->and($message->event())->toBeInstanceOf($event::class)
        ->and($message->isMessaging())->toBeFalse()
        ->and($message->header('foo'))->toBeNull()
        ->and($message->headers())->toBeEmpty()
        ->and($message->has('foo'))->toBeFalse()
        ->and($message->hasNot('foo'))->toBeTrue();
});

it('create message instance with headers', function () {
    $event = new stdClass();
    $headers = ['foo' => 'bar'];
    $message = new Message($event, $headers);

    expect($message)->toBeInstanceOf(Message::class)
        ->and($event)->not()->toBe($message->event())
        ->and($message->event())->toBeInstanceOf($event::class)
        ->and($message->isMessaging())->toBeFalse()
        ->and($message->header('foo'))->toBe('bar')
        ->and($message->headers())->toBe($headers)
        ->and($message->headers())->toEqual($headers)
        ->and($message->has('foo'))->toBeTrue()
        ->and($message->hasNot('baz'))->toBeTrue()
        ->and($message->event())->not->toHaveProperty('headers');
});

it('create message instance with domain instance', function (Messaging $event) {
    $headers = ['foo' => 'bar'];
    $message = new Message($event, $headers);

    expect($message)->toBeInstanceOf(Message::class)
        ->and($event)->not()->toBe($message->event())
        ->and($event->toContent())->toEqual($message->event()->toContent())
        ->and($message->event())->toBeInstanceOf($event::class)
        ->and($message->isMessaging())->toBeTrue()
        ->and($message->headers())->toBe($headers)
        ->and($message->headers())->toEqual($headers)
        ->and($message->has('foo'))->toBeTrue()
        ->and($message->hasNot('baz'))->toBeTrue()
        ->and($message->event())->toHaveProperty('headers')
        ->and($message->event())->toHaveProperty('content')
        ->and($message->event()->header('foo'))->toBe('bar');
})->with('provideMessaging');

it('override message header', function (Messaging $event) {
    $headers = ['foo' => 'bar'];
    $message = new Message($event, $headers);

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->header('foo'))->toBe('bar')
        ->and($message->headers())->toBe($headers);

    $newMessage = $message->withHeader('foo', 'baz');

    expect($message)->not()->toBe($newMessage)
        ->and($message->header('foo'))->toBe('bar')
        ->and($message->headers())->toBe($headers)
        ->and($newMessage->header('foo'))->toBe('baz')
        ->and($newMessage->headers())->toEqual(['foo' => 'baz'])
        ->and($newMessage->event()->headers())->toBe(['foo' => 'baz']);
})->with('provideMessaging');

it('override message headers', function (Messaging $event) {
    $headers = ['foo' => 'bar'];
    $message = new Message($event, $headers);

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->header('foo'))->toBe('bar')
        ->and($message->headers())->toBe($headers);

    $newMessage = $message->withHeaders(['foo' => 'baz']);

    expect($message)->not()->toBe($newMessage)
        ->and($message->header('foo'))->toBe('bar')
        ->and($message->headers())->toBe($headers)
        ->and($newMessage->header('foo'))->toBe('baz')
        ->and($newMessage->headers())->toEqual(['foo' => 'baz'])
        ->and($newMessage->event()->headers())->toBe(['foo' => 'baz']);
})->with('provideMessaging');

it('raise exception when message event is instance of message', function () {
    new Message(new Message(new stdClass()), []);
})->throws(InvalidArgumentException::class, 'Message event cannot be an instance of itself');

it('raise exception when non empty headers from event or message are not equals on instantiation', function () {
    $event = SomeCommand::fromContent(['name' => 'steph bug'])->withHeader('foo', 'nope');

    new Message($event, ['foo' => 'bar']);
})->throws(RuntimeException::class, 'Invalid headers consistency for event class');
