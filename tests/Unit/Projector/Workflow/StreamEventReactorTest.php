<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Closure;
use Provider\Event\PerformWhenThresholdIsReached;
use Scope\ProjectorScope;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\Header;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserState;
use Storm\Projector\Stream\StreamEventReactor;
use Storm\Projector\Workflow\Notification\Command\BatchStreamIncrements;
use Storm\Projector\Workflow\Notification\Command\StreamEventAcked;
use Storm\Projector\Workflow\Notification\Command\UserStateChanged;
use Storm\Projector\Workflow\Notification\Promise\CurrentUserState;
use Storm\Projector\Workflow\Notification\Promise\IsSprintRunning;
use Storm\Projector\Workflow\Notification\Promise\IsUserStateInitialized;
use Storm\Projector\Workflow\Notification\Promise\StreamEventProcessed;
use Storm\Stream\StreamPosition;

use function is_array;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->event = mock(DomainEvent::class);
    $this->eventTime = '2021-10-10 10:10:10';
    $this->projector = mock(ProjectorScope::class);
});

dataset('no gap', ['null gap' => [null], 'in gap' => [GapType::IN_GAP]]);
dataset('has gap', [GapType::UNRECOVERABLE_GAP, GapType::RECOVERABLE_GAP]);

function shouldExpectGap(string $streamName, StreamPosition $position, ?GapType $gapType): Closure
{
    return function ($that) use ($streamName, $position, $gapType): void {
        $checkpoint = CheckpointFactory::from(
            $streamName,
            $position->value,
            $that->eventTime,
            '2021-10-10 10:10:10',
            [],
            $gapType
        );

        $that->hub->expects('await')
            ->withArgs(function ($notification) use ($streamName, $position, $that): bool {
                return $notification instanceof StreamEventProcessed
                    && $notification->streamName === $streamName
                    && $notification->streamPosition === $position->value
                    && $notification->eventTime === $that->eventTime;
            })->andReturn($checkpoint);
    };
}

function shouldInitUserState(?array $state): Closure
{
    return function ($that) use ($state): void {
        $init = is_array($state);

        $that->hub->expects('await')->once()->with(IsUserStateInitialized::class)->andReturn($init);

        $init
            ? $that->hub->expects('await')->with(CurrentUserState::class)->andReturn($state)
            : $that->hub->expects('await')->with(CurrentUserState::class)->never();
    };
}

test('react on event acked', function (bool $stillRunning, ?GapType $gapType) {
    $this->event->expects('header')->with(Header::EVENT_TIME)->andReturn($this->eventTime);

    $streamName = 'stream-1';
    $streamPosition = new StreamPosition(10);
    $userState = ['count' => 0];

    $reactors = function (EventScope $scope): void {
        $scope
            ->ack($this->event::class)
            ->then(function (DomainEvent $event, ProjectorScope $projector, UserState $userState) {
                $userState->increment(value: 5);
            });
    };

    shouldExpectGap($streamName, $streamPosition, $gapType)($this);
    shouldInitUserState($userState)($this);

    $this->hub->expects('emit')->with(BatchStreamIncrements::class);
    $this->hub->expects('emit')->with(UserStateChanged::class, ['count' => 5]);
    $this->hub->expects('emit')->with(StreamEventAcked::class, $this->event::class);
    $this->hub->expects('emitWhen')->twice()
        ->withArgs(function (bool $condition, Closure $callback) {
            $callback($this->hub);

            return $condition === true;
        });

    $this->hub->expects('trigger')
        ->withArgs(function (object $notification) {
            return $notification instanceof PerformWhenThresholdIsReached;
        });

    $this->hub->expects('await')->with(IsSprintRunning::class)->andReturn($stillRunning);

    //
    $streamEventReactor = new StreamEventReactor($reactors, $this->projector, false);
    $continue = $streamEventReactor($this->hub, $streamName, $this->event, $streamPosition);

    expect($continue)->toBe($stillRunning);
})
    ->with('keep projection running')
    ->with('no gap');

test('does not react when gap is found', function (GapType $gapType) {
    $this->event->expects('header')->with(Header::EVENT_TIME)->andReturn($this->eventTime);

    $streamName = 'stream-1';
    $streamPosition = new StreamPosition(10);

    shouldExpectGap($streamName, $streamPosition, $gapType)($this);

    $this->hub->shouldNotReceive('await')->with(IsUserStateInitialized::class);
    $this->hub->shouldNotReceive('await')->with(CurrentUserState::class);
    $this->hub->shouldNotReceive('emit')->with(BatchStreamIncrements::class);
    $this->hub->shouldNotReceive('emit')->with(UserStateChanged::class, ['count' => 5]);
    $this->hub->shouldNotReceive('emit')->with(StreamEventAcked::class, $this->event::class);
    $this->hub->shouldNotReceive('emitWhen');
    $this->hub->shouldNotReceive('trigger');
    $this->hub->shouldNotReceive('await')->with(IsSprintRunning::class);

    //
    $streamEventReactor = new StreamEventReactor(fn () => null, $this->projector, false);
    $continue = $streamEventReactor($this->hub, $streamName, $this->event, $streamPosition);

    expect($continue)->toBeFalse();
})->with('has gap');

test('react on event but does notify of non acked event', function (bool $stillRunning, ?GapType $gapType) {
    $this->event->expects('header')->with(Header::EVENT_TIME)->andReturn($this->eventTime);

    $streamName = 'stream-1';
    $streamPosition = new StreamPosition(10);
    $userState = ['count' => 0];

    $reactors = function (EventScope $scope): void {
        $scope->userState->increment(value: 20);
    };

    shouldExpectGap($streamName, $streamPosition, $gapType)($this);
    shouldInitUserState($userState)($this);

    $this->hub->expects('emit')->with(BatchStreamIncrements::class);
    $this->hub->expects('emit')->with(UserStateChanged::class, ['count' => 20]);
    $this->hub->expects('emit')->with(StreamEventAcked::class, $this->event::class);
    $this->hub->expects('emitWhen')->twice()
        ->withArgs(function (bool $condition, Closure $callback) {
            if ($condition) {
                $callback($this->hub);
            }

            return true;
        });

    $this->hub
        ->expects('trigger')
        ->withArgs(function (object $notification) {
            return $notification instanceof PerformWhenThresholdIsReached;
        });

    $this->hub->expects('await')->with(IsSprintRunning::class)->andReturn($stillRunning);

    //
    $streamEventReactor = new StreamEventReactor($reactors, $this->projector, false);
    $continue = $streamEventReactor($this->hub, $streamName, $this->event, $streamPosition);

    expect($continue)->toBe($stillRunning);
})
    ->with('projection running')
    ->with('no gap');

test('react on acked event but does not notify null user state', function (bool $stillRunning, ?GapType $gapType) {
    $this->event->expects('header')->with(Header::EVENT_TIME)->andReturn($this->eventTime);

    $streamName = 'stream-1';
    $streamPosition = new StreamPosition(10);

    $reactors = function (EventScope $scope): void {
        $scope->ack($this->event::class);

        expect($scope->userState)->toBeNull();
    };

    shouldExpectGap($streamName, $streamPosition, $gapType)($this);
    shouldInitUserState(null)($this);

    $this->hub->expects('emit')->with(BatchStreamIncrements::class);
    $this->hub->shouldNotReceive('emit')->with(UserStateChanged::class, []);
    $this->hub->expects('emit')->with(StreamEventAcked::class, $this->event::class);
    $this->hub->expects('emitWhen')
        ->twice()
        ->withArgs(function (bool $condition, Closure $callback) {
            if ($condition) {
                $callback($this->hub);
            }

            return true;
        });

    $this->hub->expects('trigger')->withArgs(fn (object $notification) => $notification instanceof PerformWhenThresholdIsReached);
    $this->hub->expects('await')->with(IsSprintRunning::class)->andReturn($stillRunning);

    //
    $streamEventReactor = new StreamEventReactor($reactors, $this->projector, false);
    $continue = $streamEventReactor($this->hub, $streamName, $this->event, $streamPosition);

    expect($continue)->toBe($stillRunning);
})
    ->with('projection running')
    ->with('no gap');

test('dispatch signal', function () {})->todo();
