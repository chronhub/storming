<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Activity;

use ArrayIterator;
use Closure;
use Generator;
use Storm\Clock\ClockFactory;
use Storm\Contract\Message\EventHeader;
use Storm\Contract\Message\Header;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Stream\Iterator\MergeStreamIterator;
use Storm\Projector\Stream\Iterator\StreamIterator;
use Storm\Projector\Workflow\Activity\HandleStreamEvent;
use Storm\Projector\Workflow\Notification\Command\StreamProcessed;
use Storm\Projector\Workflow\Notification\Promise\IsSprintRunning;
use Storm\Projector\Workflow\Notification\Promise\PullBatchStream;
use Storm\Tests\Stubs\Double\Message\SomeEvent;

// todo ue the merge stream iterator stub
beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
});

function eventProcessor(array &$expectedPositions, int $stopAt = 0): Closure
{
    return function (NotificationHub $hub, string $streamName, SomeEvent $event) use (&$expectedPositions, $stopAt) {
        if ($stopAt > 0 && $event->header('expected_position') === $stopAt) {
            return false;
        }

        $expectedPositions[] = $event->header('expected_position');

        return true;
    };
}

test('return early when streams are not an instance of merge stream iterator', function (mixed $value) {
    $this->hub->expects('await')->with(PullBatchStream::class)->andReturn($value);
    $this->hub->shouldNotReceive('emit');
    $this->hub->shouldNotReceive('await');

    $activity = new HandleStreamEvent(fn () => null);

    $result = $activity($this->hub, fn () => true);

    expect($result)->toBeTrue();
})->with([
    'null' => fn () => null,
    'array' => fn () => ['foo'],
    'empty array' => fn () => [],
    'array iterator' => fn () => new ArrayIterator(['foo']),
]);

test('iterate over all streams', function () {
    $streams = getMergeStreams();

    expect($streams->count())->toBe(5);

    $this->hub->expects('await')->with(PullBatchStream::class)->andReturn($streams);
    $this->hub->expects('emit')->with(StreamProcessed::class, 'stream-1')->times(2);
    $this->hub->expects('emit')->with(StreamProcessed::class, 'stream-2')->times(3);
    $this->hub->expects('await')->with(IsSprintRunning::class)->times(5)->andReturn(true);

    $expectedPositions = [];
    $eventProcessor = eventProcessor($expectedPositions);

    $activity = new HandleStreamEvent($eventProcessor);

    $result = $activity($this->hub, fn () => fn () => -1);

    expect($result())
        ->toBe(-1)
        ->and($expectedPositions)->toBe([1, 3, 4, 6, 8]);
});

test('stop iterating when stream processor detect gap', function () {
    $streams = getMergeStreams();

    expect($streams->count())->toBe(5);

    $this->hub->expects('await')->with(PullBatchStream::class)->andReturn($streams);
    $this->hub->expects('emit')->with(StreamProcessed::class, 'stream-1')->times(2);
    $this->hub->expects('emit')->with(StreamProcessed::class, 'stream-2')->times(2);
    $this->hub->expects('await')->with(IsSprintRunning::class)->times(3)->andReturn(true);

    $expectedPositions = [];
    $eventProcessor = eventProcessor($expectedPositions, 6);

    $activity = new HandleStreamEvent($eventProcessor);

    $result = $activity($this->hub, fn () => fn () => -1);

    expect($result())
        ->toBe(-1)
        ->and($expectedPositions)->toBe([1, 3, 4]);
});

test('stop iterating when is sprint running return false', function () {
    $streams = getMergeStreams();

    expect($streams->count())->toBe(5);

    $this->hub->expects('await')->with(PullBatchStream::class)->andReturn($streams);
    $this->hub->expects('emit')->with(StreamProcessed::class, 'stream-1')->never();
    $this->hub->expects('emit')->with(StreamProcessed::class, 'stream-2')->once();
    $this->hub->expects('await')->with(IsSprintRunning::class)->once()->andReturn(false);

    $expectedPositions = [];
    $eventProcessor = eventProcessor($expectedPositions);

    $activity = new HandleStreamEvent($eventProcessor);

    $result = $activity($this->hub, fn () => fn () => -1);

    expect($result())
        ->toBe(-1)
        ->and($expectedPositions)->toBe([1]);
});

function getMergeStreams(): MergeStreamIterator
{
    $stream1 = new StreamIterator(yieldStream1());
    $stream2 = new StreamIterator(yieldStream2());

    $clock = ClockFactory::create();

    return new MergeStreamIterator($clock, collect([[$stream1, 'stream-1'], [$stream2, 'stream-2']]));
}

function yieldStream1(): Generator
{
    $stream = 'stream-1';

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 1,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000003',
            'expected_position' => 3,
        ]
    );

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 2,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000006',
            'expected_position' => 6,
        ]
    );

    return 2;
}

function yieldStream2(): Generator
{
    $stream = 'stream-2';

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 1,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000001',
            'expected_position' => 1,
        ]
    );

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 2,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000004',
            'expected_position' => 4,
        ]
    );

    yield SomeEvent::fromContent(['stream' => $stream])->withHeaders(
        [
            EventHeader::INTERNAL_POSITION => 8,
            Header::EVENT_TIME => '2024-06-20T10:22:05.000008',
            'expected_position' => 8,
        ]
    );

    return 3;
}
