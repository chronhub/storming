<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Tracker;

use stdClass;
use Storm\Contract\Story\Story;
use Storm\Contract\Tracker\MessageStory;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Message\Message;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tracker\Draft;
use Storm\Tracker\GenericListener;
use Storm\Tracker\TrackMessage;

beforeEach(function (): void {
    $this->tracker = new TrackMessage;
});

afterEach(function (): void {
    $this->tracker = null;
});

describe('create', function (): void {
    test('new tracker instance', function () {
        $listeners = $this->tracker->listeners();

        expect($this->tracker)->toBeInstanceOf(MessageTracker::class)
            ->and($listeners)->toBeEmpty()
            ->and($listeners)->not()->toBe($this->tracker->listeners())
            ->and($listeners)->toEqual($this->tracker->listeners());
    });

    test('new story instance', function () {
        $story = $this->tracker->newStory('reporter_event');

        expect($story)->toBeInstanceOf(MessageStory::class)
            ->toBeInstanceOf(Draft::class)
            ->and($story->currentEvent())->toBe('reporter_event');
    });
});

describe('watch', function (): void {
    test('some event with event listener instance', function () {
        $eventListener = new GenericListener('some_event', fn (): string => 'story', 10);
        $listeners = $this->tracker->listen($eventListener);
        $listener = $listeners[0];

        expect($listener)->toBe($eventListener)
            ->and($this->tracker->listeners())->toHaveCount(1)
            ->and($listener->name())->toBe('some_event')
            ->and($listener->story()())->toBe('story')
            ->and($listener->priority())->toBe(10);
    });

    test('and dispatch event', function () {
        expect($this->tracker->listeners())->toHaveCount(0);

        $listener = $this->tracker->onDispatch(fn () => 'story');

        expect($this->tracker->listeners())->toHaveCount(1)
            ->and($listener->name())->toBe(Story::DISPATCH_EVENT)
            ->and($listener->story()())->toBe('story');
    });

    test('and finalize event', function () {
        expect($this->tracker->listeners())->toHaveCount(0);

        $listener = $this->tracker->onFinalize(fn () => 'story');

        expect($this->tracker->listeners())->toHaveCount(1)
            ->and($listener->name())->toBe(Story::FINALIZE_EVENT)
            ->and($listener->story()())->toBe('story');
    });

    test('events with priority', function () {
        $this->tracker->onDispatch(fn () => 'story', 2);
        $this->tracker->onDispatch(fn () => 'story');
        $this->tracker->onDispatch(fn () => 'story', 0);

        expect($this->tracker->listeners())->toHaveCount(3)
            ->and($this->tracker->listeners()[0]->priority())->toBe(2)
            ->and($this->tracker->listeners()[1]->priority())->toBe(1)
            ->and($this->tracker->listeners()[2]->priority())->toBe(0);
    });
});

describe('dispatch', function (): void {
    test('some event', function () {
        $this->tracker->onDispatch(function (MessageStory $story): void {
            $story->withMessage(new Message(new stdClass));
        });

        $story = $this->tracker->newStory(Story::DISPATCH_EVENT);

        $this->tracker->disclose($story);

        expect($story->message())->toBeInstanceOf(Message::class)
            ->and($story->message()->event())->toBeInstanceOf(stdClass::class);
    });

    test('some event till propagation event is not stopped', function () {
        $this->tracker->onDispatch(function (MessageStory $story): void {
            $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'bar'])));

        }, 10);

        $this->tracker->onDispatch(function (MessageStory $story): void {
            $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'baz'])));
            $story->stop(true);
        }, 100);

        $story = $this->tracker->newStory(Story::DISPATCH_EVENT);

        $this->tracker->disclose($story);

        expect($story->message())->toBeInstanceOf(Message::class)
            ->and($story->message()->event()->toContent()['foo'])->toBe('baz');
    });

    test('some event with ordered priorities', function () {
        $this->tracker->onDispatch(function (MessageStory $story): void {
            $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'bar'])));

            expect($story->message()->event()->toContent()['foo'])->toBe('bar')
                ->and($story->isStopped())->toBeFalse();
        }, 100);

        $this->tracker->onDispatch(function (MessageStory $story): void {
            $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'baz'])));

            expect($story->message()->event()->toContent()['foo'])->toBe('baz')
                ->and($story->isStopped())->toBeFalse();
        }, 90);

        $story = $this->tracker->newStory(Story::DISPATCH_EVENT);

        $this->tracker->disclose($story);

        expect($story->message())->toBeInstanceOf(Message::class)
            ->and($story->message()->event())->toBeInstanceOf(SomeCommand::class)
            ->and($story->message()->event()->toContent()['foo'])->toBe('baz')
            ->and($story->isStopped())->toBeFalse();
    });

    test('some event until a truthy callback', function () {
        $this->tracker->onDispatch(function (MessageStory $story): void {
            $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'bar'])));

            expect($story->message()->event()->toContent()['foo'])->toBe('bar')
                ->and($story->isStopped())->toBeFalse();

        }, 100);

        $this->tracker->onDispatch(function (MessageStory $story): void {
            $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'nope'])));
        }, 90);

        $story = $this->tracker->newStory(Story::DISPATCH_EVENT);

        $this->tracker->discloseUntil($story, function (MessageStory $story): bool {
            if ($story->message()->event()->toContent()['foo'] === 'bar') {
                $story->stop(true);

                return true;
            }

            return false;
        });

        expect($story->message())->toBeInstanceOf(Message::class)
            ->and($story->message()->event())->toBeInstanceOf(SomeCommand::class)
            ->and($story->message()->event()->toContent()['foo'])->toBe('bar')
            ->and($story->isStopped())->toBeTrue();
    });
});

it('forget event', function () {
    $this->tracker->onDispatch(function (MessageStory $story): void {
        $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'bar'])));

        expect($story->message()->event()->toContent()['foo'])->toBe('bar')
            ->and($story->isStopped())->toBeFalse();
    }, 100);

    $listenerToForget = $this->tracker->onDispatch(function (MessageStory $story): void {
        $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'baz'])));
    }, 90);

    $story = $this->tracker->newStory(Story::DISPATCH_EVENT);

    expect($this->tracker->listeners())->toHaveCount(2);

    $this->tracker->forget($listenerToForget);

    expect($this->tracker->listeners())->toHaveCount(1);

    $this->tracker->disclose($story);

    expect($story->message())->toBeInstanceOf(Message::class)
        ->and($story->message()->event())->toBeInstanceOf(SomeCommand::class)
        ->and($story->message()->event()->toContent()['foo'])->toBe('bar')
        ->and($story->isStopped())->toBeFalse();
});
