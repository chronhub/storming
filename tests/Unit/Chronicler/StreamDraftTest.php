<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Chronicler;

use stdClass;
use Storm\Chronicler\Exceptions\ConcurrencyException;
use Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Chronicler\Exceptions\UnexpectedCallback;
use Storm\Chronicler\StreamDraft;
use Storm\Contract\Message\MessageDecorator;
use Storm\Message\Message;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

describe('interact with story', function (): void {

});

describe('test promise and deferred', function (): void {
    it('resolve promise', function (mixed $callback): void {
        $draft = new StreamDraft('some_event');
        $draft->deferred(fn (): mixed => $callback);

        expect($draft->promise())->toBe($callback);
    })->with('someTypes');

    it('override promise', function (): void {
        $draft = new StreamDraft('some_event');
        $draft->deferred(fn (): int => 42);

        expect($draft->promise())->toBe(42);

        $draft->deferred(fn (): bool => false);

        expect($draft->promise())->toBe(false);
    });
});

describe('test exception', function (): void {
    it('raise exception when callback is not set', function (): void {
        $draft = new StreamDraft('some_event');
        $draft->promise();
    })->throws(UnexpectedCallback::class, 'No event callback has been set');

    it('test story exception', function (): void {
        $draft = new StreamDraft('some_event');

        expect($draft->exception())->toBeNull();
    });

    it('test stream exception', function (): void {
        $draft = new StreamDraft('some_event');

        expect($draft->hasStreamAlreadyExits())->toBeFalse()
            ->and($draft->hasStreamNotFound())->toBeFalse()
            ->and($draft->hasConcurrency())->toBeFalse();
    });

    it('test stream already exists exception', function (): void {
        $draft = new StreamDraft('some_event');

        expect($draft->hasStreamAlreadyExits())->toBeFalse();

        $exception = StreamAlreadyExists::withStreamName(new StreamName('foo'));
        $draft->withRaisedException($exception);

        expect($draft->hasException())->toBe(true)
            ->and($draft->hasStreamAlreadyExits())->toBe(true)
            ->and($draft->exception())->toBe($exception);
    });

    it('test stream not found exception', function (): void {
        $draft = new StreamDraft('some_event');

        expect($draft->hasStreamNotFound())->toBeFalse();

        $exception = StreamNotFound::withStreamName(new StreamName('foo'));
        $draft->withRaisedException($exception);

        expect($draft->hasException())->toBe(true)
            ->and($draft->hasStreamNotFound())->toBe(true)
            ->and($draft->exception())->toBe($exception);
    });

    it('test concurrency exception', function (): void {
        $draft = new StreamDraft('some_event');

        expect($draft->hasConcurrency())->toBeFalse();

        $exception = new ConcurrencyException('some error');
        $draft->withRaisedException($exception);

        expect($draft->hasException())->toBe(true)
            ->and($draft->hasConcurrency())->toBe(true)
            ->and($draft->exception())->toBe($exception);
    });
});

describe('decorate stream event', function (): void {
    it('test decorate stream event', function (MessageDecorator $eventDecorator): void {

        $stream = new Stream(new StreamName('customer'), [SomeEvent::fromContent(['name' => 'steph bug'])]);

        expect($stream->events()->current()->headers())->toBeEmpty();

        $draft = new StreamDraft('some event');
        $draft->deferred(fn (): Stream => $stream);
        $draft->decorate($eventDecorator);
        $newStream = $draft->promise();

        expect($newStream)->toBeInstanceOf(Stream::class);

        $streamEvent = $newStream->events()->current();

        expect($streamEvent)->toBeInstanceOf(SomeEvent::class)
            ->and($streamEvent->toContent())->toBe(['name' => 'steph bug'])
            ->and($streamEvent->headers())->toBe(['foo' => 'bar']);

    })->with('eventDecorator');

    it('test exception raise when stream events not set to be decorated', function (mixed $value, MessageDecorator $eventDecorator): void {
        $draft = new StreamDraft('some event');
        $draft->deferred(fn (): mixed => $value);
        $draft->decorate($eventDecorator);
    })
        ->throws(UnexpectedCallback::class, 'No stream has been set as event callback')
        ->with('someTypes')
        ->with('eventDecorator');
});

dataset('someTypes', [
    42,
    true,
    false,
    new stdClass(),
]);

dataset('eventDecorator', [
    fn (): MessageDecorator => new class implements MessageDecorator
    {
        public function decorate(Message $message): Message
        {
            return $message->withHeader('foo', 'bar');
        }
    },
]);
