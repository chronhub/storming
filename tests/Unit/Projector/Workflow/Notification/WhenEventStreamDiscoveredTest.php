<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Notification;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\NewEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\NoEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Handler\WhenEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Promise\CurrentNewEventStreams;
use Storm\Projector\Workflow\Notification\Promise\HasEventStreamDiscovered;
use Storm\Tests\Stubs\Double\Message\AnotherEvent;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

use function count;
use function in_array;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->event = new EventStreamDiscovered();
});

test('notify no stream discovered', function () {
    $this->hub->expects('await')->with(HasEventStreamDiscovered::class)->andReturns(false);

    $this->hub->expects('emit')->with(NoEventStreamDiscovered::class);

    (new WhenEventStreamDiscovered())($this->hub, $this->event);
});

test('notify stream discovered', function (array $newEventStreams) {
    $this->hub->expects('await')->with(HasEventStreamDiscovered::class)->andReturns(true);
    $this->hub->shouldNotReceive('emit')->with(NoEventStreamDiscovered::class);

    $this->hub->expects('await')
        ->with(CurrentNewEventStreams::class)->andReturns($newEventStreams);

    $this->hub->expects('emit')->withArgs(
        function (string $event, string $streamEvent) use ($newEventStreams) {
            return $event === NewEventStreamDiscovered::class
                && in_array($streamEvent, $newEventStreams, true);
        })->times(count($newEventStreams));

    (new WhenEventStreamDiscovered())($this->hub, $this->event);
})->with([
    [[SomeEvent::class]],
    [[SomeEvent::class, AnotherEvent::class]],
]);
