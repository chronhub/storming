<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Tracker;

use Generator;
use React\Promise\PromiseInterface;
use RuntimeException;
use Storm\Message\Message;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tracker\Draft;

use function iterator_to_array;

it('create new instance with given event', function () {
    $tracker = new Draft('event');

    expect($tracker->currentEvent())->toBe('event')
        ->and($tracker->isStopped())->toBeFalse()
        ->and($tracker->hasException())->toBeFalse()
        ->and($tracker->exception())->toBeNull()
        ->and($tracker->handlers())->toBeInstanceOf(Generator::class)
        ->and($tracker->promise())->toBeNull()
        ->and($tracker->isHandled())->toBeFalse();
});

it('override current event', function () {
    $tracker = new Draft('event');

    expect($tracker->currentEvent())->toBe('event');

    $tracker->withEvent('dispatch');

    expect($tracker->currentEvent())->toBe('dispatch');
});

it('test transient message', function () {
    $draft = new Draft('event');

    expect($draft->transientMessage())->toBeNull();

    $transient = new Message(new SomeCommand(['name' => 'steph']));
    $draft->withTransientMessage($transient);
    $extracted = $draft->pullTransientMessage();

    expect($extracted)->toBe($transient);

    $draft->withMessage($transient);
    $message = $draft->message();

    expect($message)->toBe($transient)->toBe($message);
});

it('set consumers', function () {
    $draft = new Draft('event');

    expect($draft->handlers())->toBeInstanceOf(Generator::class);

    $messageHandlers = [fn () => 'consumer'];
    $draft->withHandlers($messageHandlers);
    $consumers = $draft->handlers();

    expect($consumers)
        ->toBeInstanceOf(Generator::class)
        ->and(iterator_to_array($consumers))->toEqual($messageHandlers);
});

it('mark message handled', function () {
    $draft = new Draft('event');

    expect($draft->isHandled())->toBeFalse();

    $draft->markHandled(true);

    expect($draft->isHandled())->toBeTrue();
});

it('set promise', function () {
    $draft = new Draft('event');

    expect($draft->promise())->toBeNull();

    $promise = $this->createMock(PromiseInterface::class);

    $draft->withPromise($promise);

    expect($draft->promise())->toBe($promise);
});

it('set exception', function () {
    $draft = new Draft('event');

    expect($draft->hasException())->toBeFalse();

    $exception = new RuntimeException('some error');

    $draft->withRaisedException($exception);

    expect($draft->hasException())->toBeTrue()
        ->and($draft->exception())->toBe($exception);
});

it('override exception', function () {
    $draft = new Draft('event');

    expect($draft->hasException())->toBeFalse();

    $exception = new RuntimeException('some error');

    $draft->withRaisedException($exception);

    expect($draft->hasException())->toBeTrue()
        ->and($draft->exception())->toBe($exception);

    $exception = new RuntimeException('another error');
    $draft->withRaisedException($exception);

    expect($draft->hasException())->toBeTrue()
        ->and($draft->exception())->toBe($exception);
});

it('reset exception', function () {
    $draft = new Draft('event');

    expect($draft->hasException())->toBeFalse();

    $exception = new RuntimeException('some error');

    $draft->withRaisedException($exception);

    expect($draft->hasException())->toBeTrue()
        ->and($draft->exception())->toBe($exception);

    $draft->resetException();

    expect($draft->hasException())->toBeFalse()
        ->and($draft->exception())->toBeNull();
});
