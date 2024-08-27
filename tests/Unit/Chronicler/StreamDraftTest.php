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

use function PHPUnit\Framework\assertInstanceOf;

beforeEach(function (): void {
    $this->draft = new StreamDraft('some_event');
});

afterEach(function (): void {
    $this->draft = null;
});

dataset('some types', [
    42,
    true,
    false,
    new stdClass(),
]);

dataset('event decorator', [
    fn (): MessageDecorator => new class implements MessageDecorator
    {
        public function decorate(Message $message): Message
        {
            return $message->withHeader('foo', 'bar');
        }
    },
]);

describe('resolve', function (): void {
    test('promise', function (mixed $callback): void {
        $this->draft->deferred(fn (): mixed => $callback);

        expect($this->draft->promise())->toBe($callback);
    })->with('some types');

    test('override promise', function (): void {
        $this->draft->deferred(fn (): int => 42);

        expect($this->draft->promise())->toBe(42);

        $this->draft->deferred(fn (): bool => false);

        expect($this->draft->promise())->toBeFalse();
    });
});

describe('exception', function (): void {
    test('accessor', function (): void {
        expect($this->draft->exception())->toBeNull()
            ->and($this->draft->hasStreamAlreadyExits())->toBeFalse()
            ->and($this->draft->hasStreamNotFound())->toBeFalse()
            ->and($this->draft->hasConcurrency())->toBeFalse();
    });

    test('raised when callback is not set', function (): void {
        $this->draft->promise();
    })->throws(UnexpectedCallback::class, 'No event callback has been set');

    test('stream already exists', function (): void {
        expect($this->draft->hasStreamAlreadyExits())->toBeFalse();

        $exception = StreamAlreadyExists::withStreamName(new StreamName('foo'));
        $this->draft->withRaisedException($exception);

        expect($this->draft->hasException())->toBeTrue()
            ->and($this->draft->hasStreamAlreadyExits())->toBeTrue()
            ->and($this->draft->exception())->toBe($exception);
    });

    test('stream not found', function (): void {
        expect($this->draft->hasStreamNotFound())->toBeFalse();

        $exception = StreamNotFound::withStreamName(new StreamName('foo'));
        $this->draft->withRaisedException($exception);

        expect($this->draft->hasException())->toBeTrue()
            ->and($this->draft->hasStreamNotFound())->toBeTrue()
            ->and($this->draft->exception())->toBe($exception);
    });

    test('concurrency error', function (): void {
        expect($this->draft->hasConcurrency())->toBeFalse();

        $exception = new ConcurrencyException('some error');
        $this->draft->withRaisedException($exception);

        expect($this->draft->hasException())->toBeTrue()
            ->and($this->draft->hasConcurrency())->toBeTrue()
            ->and($this->draft->exception())->toBe($exception);
    });
});

describe('decorate stream event', function (): void {
    test('with given decorator', function (MessageDecorator $eventDecorator): void {
        $stream = new Stream(new StreamName('customer'), [SomeEvent::fromContent(['name' => 'steph bug'])]);

        expect($stream->events()->current()->headers())->toBeEmpty();

        $this->draft->deferred(fn (): Stream => $stream);
        $this->draft->decorate($eventDecorator);

        $newStream = $this->draft->promise();

        assertInstanceOf(Stream::class, $newStream);

        $streamEvent = $newStream->events()->current();

        expect($streamEvent)->toBeInstanceOf(SomeEvent::class)
            ->and($streamEvent->toContent())->toBe(['name' => 'steph bug'])
            ->and($streamEvent->headers())->toBe(['foo' => 'bar']);

    })->with('event decorator');

    test('raise exception with invalid deferred type', function (mixed $value, MessageDecorator $eventDecorator): void {
        $this->draft->deferred(fn (): mixed => $value);
        $this->draft->decorate($eventDecorator);
    })
        ->throws(UnexpectedCallback::class, 'No stream has been set as event callback')
        ->with('some types')
        ->with('event decorator');
});
