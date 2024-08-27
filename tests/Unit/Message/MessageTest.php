<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Message;

use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Storm\Contract\Message\Messaging;
use Storm\Message\Message;
use Storm\Tests\Stubs\Double\Message\SomeCommand;

describe('create message', function (): void {
    test('with object event', function () {
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

    test('with given headers', function () {
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

    test('with domain instance', function (Messaging $event) {
        $headers = ['foo' => 'bar'];
        $message = new Message($event, $headers);

        expect($message)->toBeInstanceOf(Message::class)
            ->and($event)->not()->toBe($message->event())
            ->and($event->toContent())->toEqual($message->event()->toContent())
            ->and($message->event())->toBeInstanceOf($event::class)
            ->and($message->isMessaging())->toBeTrue()
            ->and($message->headers())->toBe($headers)
            ->and($message->has('foo'))->toBeTrue()
            ->and($message->hasNot('baz'))->toBeTrue()
            ->and($message->event())->toHaveProperty('headers')
            ->and($message->event())->toHaveProperty('content')
            ->and($message->event()->header('foo'))->toBe('bar');
    })->with('provideMessaging');
});

describe('message headers', function (): void {
    test('override header', function (Messaging $event) {
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

    test('override headers', function (Messaging $event) {
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
});

describe('raise exception', function (): void {
    test('when message event is instance of message', function () {
        new Message(new Message(new stdClass()), []);
    })->throws(InvalidArgumentException::class, 'Message event cannot be an instance of message');

    test('when non empty headers from event or message are not equals on instantiation', function () {
        $event = SomeCommand::fromContent(['name' => 'steph bug'])->withHeader('foo', 'nope');

        new Message($event, ['foo' => 'bar']);
    })->throws(RuntimeException::class, 'Invalid headers consistency for event class');
});
