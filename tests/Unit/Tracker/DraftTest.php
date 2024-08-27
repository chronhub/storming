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

beforeEach(function (): void {
    $this->draft = new Draft('some_event');
});

afterEach(function (): void {
    $this->draft = null;
});

it('create new instance with given event', function () {
    expect($this->draft->currentEvent())->toBe('some_event')
        ->and($this->draft->isStopped())->toBeFalse()
        ->and($this->draft->hasException())->toBeFalse()
        ->and($this->draft->exception())->toBeNull()
        ->and($this->draft->handlers())->toBeInstanceOf(Generator::class)
        ->and($this->draft->promise())->toBeNull()
        ->and($this->draft->isHandled())->toBeFalse();
});

it('override current event', function () {
    expect($this->draft->currentEvent())->toBe('some_event');

    $this->draft->withEvent('dispatch');

    expect($this->draft->currentEvent())->toBe('dispatch');
});

it('test transient message', function () {

    expect($this->draft->transientMessage())->toBeNull();

    $transient = new Message(new SomeCommand(['name' => 'steph']));
    $this->draft->withTransientMessage($transient);
    $extracted = $this->draft->pullTransientMessage();

    expect($extracted)->toBe($transient);

    $this->draft->withMessage($transient);
    $message = $this->draft->message();

    expect($message)->toBe($transient)->toBe($message);
});

it('set consumers', function () {
    expect($this->draft->handlers())->toBeInstanceOf(Generator::class);

    $messageHandlers = [fn () => 'consumer'];
    $this->draft->withHandlers($messageHandlers);

    $consumers = $this->draft->handlers();

    expect($consumers)
        ->toBeInstanceOf(Generator::class)
        ->and(iterator_to_array($consumers))->toEqual($messageHandlers);
});

it('mark message handled', function () {
    expect($this->draft->isHandled())->toBeFalse();

    $this->draft->markHandled(true);

    expect($this->draft->isHandled())->toBeTrue();
});

it('set promise', function () {
    expect($this->draft->promise())->toBeNull();

    $promise = mock(PromiseInterface::class);

    $this->draft->withPromise($promise);

    expect($this->draft->promise())->toBe($promise);
});

describe('exception', function (): void {
    it('set', function () {
        expect($this->draft->hasException())->toBeFalse();

        $exception = new RuntimeException('some error');

        $this->draft->withRaisedException($exception);

        expect($this->draft->hasException())->toBeTrue()
            ->and($this->draft->exception())->toBe($exception);
    });

    it('override', function () {
        expect($this->draft->hasException())->toBeFalse();

        $exception = new RuntimeException('some error');

        $this->draft->withRaisedException($exception);

        expect($this->draft->hasException())->toBeTrue()
            ->and($this->draft->exception())->toBe($exception);

        $exception = new RuntimeException('another error');
        $this->draft->withRaisedException($exception);

        expect($this->draft->hasException())->toBeTrue()
            ->and($this->draft->exception())->toBe($exception);
    });

    it('reset', function () {
        expect($this->draft->hasException())->toBeFalse();

        $exception = new RuntimeException('some error');

        $this->draft->withRaisedException($exception);

        expect($this->draft->hasException())->toBeTrue()
            ->and($this->draft->exception())->toBe($exception);

        $this->draft->resetException();

        expect($this->draft->hasException())->toBeFalse()
            ->and($this->draft->exception())->toBeNull();
    });
});
