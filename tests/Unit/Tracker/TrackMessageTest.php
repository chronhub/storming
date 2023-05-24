<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Tracker;

use stdClass;
use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\MessageStory;
use Storm\Contract\Tracker\MessageTracker;
use Storm\Message\Message;
use Storm\Tests\Stubs\Double\Message\SomeCommand;
use Storm\Tracker\Draft;
use Storm\Tracker\TrackMessage;

it('can be instantiated', function () {
    $tracker = new TrackMessage();

    $listeners = $tracker->listeners();

    expect($tracker)->toBeInstanceOf(MessageTracker::class)
        ->and($listeners)->toBeEmpty()
        ->and($listeners)->not()->toBe($tracker->listeners())
        ->and($listeners)->toEqual($tracker->listeners());
});

it('create a new story', function () {
    $tracker = new TrackMessage();

    $story = $tracker->newStory('event');

    expect($story)->toBeInstanceOf(MessageStory::class)
        ->toBeInstanceOf(Draft::class)
        ->and($story->currentEvent())->toBe('event');
});

it('watch event', function () {
    $tracker = new TrackMessage();

    $listener = $tracker->watch('event', fn () => 'story');

    expect($tracker->listeners())->toHaveCount(1)
        ->and($listener->name())->toBe('event')
        ->and($listener->story()())->toBe('story');
});

it('watch reporter dispatch event', function () {
    $tracker = new TrackMessage();

    expect($tracker->listeners())->toHaveCount(0);

    $listener = $tracker->onDispatch(fn () => 'story');

    expect($tracker->listeners())->toHaveCount(1)
        ->and($listener->name())->toBe(Reporter::DISPATCH_EVENT)
        ->and($listener->story()())->toBe('story');
});

it('watch reporter finalize event', function () {
    $tracker = new TrackMessage();

    expect($tracker->listeners())->toHaveCount(0);

    $listener = $tracker->onFinalize(fn () => 'story');

    expect($tracker->listeners())->toHaveCount(1)
        ->and($listener->name())->toBe(Reporter::FINALIZE_EVENT)
        ->and($listener->story()())->toBe('story');
});

it('watch event with priority', function () {
    $tracker = new TrackMessage();

    $tracker->watch('event', fn () => 'story', 2);
    $tracker->watch('event', fn () => 'story');
    $tracker->watch('event', fn () => 'story', 0);

    expect($tracker->listeners())->toHaveCount(3)
        ->and($tracker->listeners()[0]->priority())->toBe(2)
        ->and($tracker->listeners()[1]->priority())->toBe(1)
        ->and($tracker->listeners()[2]->priority())->toBe(0);
});

it('dispatch event', function () {
    $tracker = new TrackMessage();

    $tracker->watch('event', function (MessageStory $story): void {
        $story->withMessage(new Message(new stdClass()));
    });
    $story = $tracker->newStory('event');

    $tracker->disclose($story);

    expect($story->message())->toBeInstanceOf(Message::class)
        ->and($story->message()->event())->toBeInstanceOf(stdClass::class);
});

it('dispatch event till propagation event is not stopped', function () {
    $tracker = new TrackMessage();

    $tracker->watch('event', function (MessageStory $story): void {
        $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'bar'])));

    }, 10);

    $tracker->watch('event', function (MessageStory $story): void {
        $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'baz'])));
        $story->stop(true);
    }, 100);

    $story = $tracker->newStory('event');

    $tracker->disclose($story);

    expect($story->message())->toBeInstanceOf(Message::class)
        ->and($story->message()->event()->toContent()['foo'])->toBe('baz');
});

it('dispatch event with ordered priorities', function () {
    $tracker = new TrackMessage();

    $tracker->watch('event', function (MessageStory $story): void {
        $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'bar'])));

        expect($story->message()->event()->toContent()['foo'])->toBe('bar')
            ->and($story->isStopped())->toBeFalse();
    }, 100);

    $tracker->watch('event', function (MessageStory $story): void {
        $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'baz'])));

        expect($story->message()->event()->toContent()['foo'])->toBe('baz')
            ->and($story->isStopped())->toBeFalse();
    }, 90);

    $story = $tracker->newStory('event');

    $tracker->disclose($story);

    expect($story->message())->toBeInstanceOf(Message::class)
        ->and($story->message()->event())->toBeInstanceOf(SomeCommand::class)
        ->and($story->message()->event()->toContent()['foo'])->toBe('baz')
        ->and($story->isStopped())->toBeFalse();
});

it('can dispatch event until a truthy callback', function () {
    $tracker = new TrackMessage();

    $tracker->watch('event', function (MessageStory $story): void {
        $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'bar'])));

        expect($story->message()->event()->toContent()['foo'])->toBe('bar')
            ->and($story->isStopped())->toBeFalse();

    }, 100);

    $tracker->watch('event', function (MessageStory $story): void {
        //
    }, 90);

    $story = $tracker->newStory('event');

    $tracker->discloseUntil($story, function (MessageStory $story): bool {
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

it('forget event', function () {
    $tracker = new TrackMessage();

    $tracker->watch('event', function (MessageStory $story): void {
        $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'bar'])));

        expect($story->message()->event()->toContent()['foo'])->toBe('bar')
            ->and($story->isStopped())->toBeFalse();
    }, 100);

    $listenerToForget = $tracker->watch('event', function (MessageStory $story): void {
        $story->withMessage(new Message(SomeCommand::fromContent(['foo' => 'baz'])));
    }, 90);

    $story = $tracker->newStory('event');

    $tracker->forget($listenerToForget);

    $tracker->disclose($story);

    expect($story->message())->toBeInstanceOf(Message::class)
        ->and($story->message()->event())->toBeInstanceOf(SomeCommand::class)
        ->and($story->message()->event()->toContent()['foo'])->toBe('bar')
        ->and($story->isStopped())->toBeFalse();
});
