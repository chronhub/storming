<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Reporter;

use React\Promise\Deferred;
use RuntimeException;
use Storm\Contract\Reporter\Reporter;
use Storm\Message\Message;
use Storm\Reporter\Attribute\AsSubscriber;
use Storm\Reporter\Subscriber\HandleQuery;
use Storm\Tests\Stubs\Double\Message\SomeQuery;
use Storm\Tracker\TrackMessage;

beforeEach(function () {
    $this->tracker = new TrackMessage();
    $this->tracker->listen(new HandleQuery());
    $this->story = $this->tracker->newStory(Reporter::DISPATCH_EVENT);

    expect($this->story->handlers()->current())->toBeNull()
        ->and($this->story->isHandled())->toBeFalse();
});

afterEach(function () {
    $this->tracker = null;
    $this->story = null;
});

it('assert has subscriber attribute', function () {
    expect(HandleQuery::class)->toHaveAttribute(AsSubscriber::class, [[
        'eventName' => Reporter::DISPATCH_EVENT,
        'priority' => 0,
    ]]);
});

it('handle query', function () {
    $query = SomeQuery::fromContent(['foo' => 'bar']);
    $queryHandler = function (SomeQuery $query, Deferred $promise): void {
        $promise->resolve($query->toContent());
    };

    $message = new Message($query);

    $this->story->withMessage($message);
    $this->story->withHandlers([$queryHandler]);
    $this->tracker->disclose($this->story);

    $promise = $this->story->promise();

    expect($promise)->toBePromiseResult(['foo' => 'bar'])
        ->and($this->story->isHandled())->toBeTrue();
});

it('set exception raised from handler in promise', function () {
    $exception = new RuntimeException('some query exception');
    $queryHandler = function () use ($exception): void {
        throw $exception;
    };

    $query = SomeQuery::fromContent(['foo' => 'bar']);
    $message = new Message($query);

    $this->story->withMessage($message);
    $this->story->withHandlers([$queryHandler]);
    $this->tracker->disclose($this->story);

    $promise = $this->story->promise();

    expect($promise)->toBePromiseResult($exception)
        ->and($this->story->isHandled())->toBeTrue();
});

it('does not set promise with no handler', function () {
    $query = SomeQuery::fromContent(['foo' => 'bar']);
    $message = new Message($query);

    $this->story->withMessage($message);
    $this->tracker->disclose($this->story);

    $promise = $this->story->promise();

    expect($promise)->toBeNull()
        ->and($this->story->isHandled())->toBeFalse();
});
