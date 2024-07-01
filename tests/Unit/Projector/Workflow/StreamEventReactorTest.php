<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow;

use Closure;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Message\Header;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectorScope;
use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Checkpoint\GapType;
use Storm\Projector\Scope\EventScope;
use Storm\Projector\Scope\UserStateScope;
use Storm\Projector\Workflow\Notification\Batch\BatchIncremented;
use Storm\Projector\Workflow\Notification\Checkpoint\CheckpointInserted;
use Storm\Projector\Workflow\Notification\Management\ProjectionPersistedWhenThresholdIsReached;
use Storm\Projector\Workflow\Notification\Sprint\IsSprintRunning;
use Storm\Projector\Workflow\Notification\Stream\StreamEventAcked;
use Storm\Projector\Workflow\Notification\UserState\CurrentUserState;
use Storm\Projector\Workflow\Notification\UserState\IsUserStateInitialized;
use Storm\Projector\Workflow\Notification\UserState\UserStateChanged;
use Storm\Projector\Workflow\StreamEventReactor;

use function is_array;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->event = mock(DomainEvent::class);
    $this->eventTime = '2021-10-10 10:10:10';
    $this->projector = mock(ProjectorScope::class);
});

dataset('projection running', ['still running' => [true],    'stopped' => [false]]);
dataset('no gap', ['null gap' => [null], 'in gap' => [GapType::IN_GAP]]);
dataset('has gap', [GapType::UNRECOVERABLE_GAP, GapType::RECOVERABLE_GAP]);

function shouldExpectGap(string $streamName, int $position, ?GapType $gapType): Closure
{
    return function ($that) use ($streamName, $position, $gapType): void {
        $checkpoint = CheckpointFactory::from(
            $streamName,
            $position,
            $that->eventTime,
            '2021-10-10 10:10:10',
            [],
            $gapType
        );

        $that->hub->shouldReceive('expect')->once()
            ->withArgs(function ($notification) use ($streamName, $position, $that): bool {
                return $notification instanceof CheckpointInserted
                    && $notification->streamName === $streamName
                    && $notification->streamPosition === $position
                    && $notification->eventTime === $that->eventTime;
            })->andReturn($checkpoint);
    };
}

function shouldInitUserState(?array $state): Closure
{
    return function ($that) use ($state): void {
        $init = is_array($state);

        $that->hub->expects('expect')->once()->with(IsUserStateInitialized::class)->andReturn($init);

        $init
            ? $that->hub->expects('expect')->once()->with(CurrentUserState::class)->andReturn($state)
            : $that->hub->expects('expect')->with(CurrentUserState::class)->never();
    };
}

test('react on event acked', function (bool $stillRunning, ?GapType $gapType) {
    $this->event->expects('header')->with(Header::EVENT_TIME)->andReturn($this->eventTime);

    $streamName = 'stream-1';
    $position = 10;
    $userState = ['count' => 0];

    $reactors = function (EventScope $scope): void {
        $scope
            ->ack($this->event::class)
            ->then(function (DomainEvent $event, ProjectorScope $projector, UserStateScope $userState) {
                $userState->increment(value: 5);
            });
    };

    shouldExpectGap($streamName, $position, $gapType)($this);
    shouldInitUserState($userState)($this);

    $this->hub->shouldReceive('notify')->with(BatchIncremented::class);
    $this->hub->shouldReceive('notify')->with(UserStateChanged::class, ['count' => 5]);
    $this->hub->shouldReceive('notify')->with(StreamEventAcked::class, $this->event::class);
    $this->hub->shouldReceive('notifyWhen')->twice()
        ->withArgs(function (bool $condition, Closure $callback) {
            $callback($this->hub);

            return $condition === true;
        });

    $this->hub->shouldReceive('trigger')
        ->withArgs(function (object $notification) {
            return $notification instanceof ProjectionPersistedWhenThresholdIsReached;
        });

    $this->hub->shouldReceive('expect')->with(IsSprintRunning::class)->andReturn($stillRunning);

    //
    $streamEventReactor = new StreamEventReactor($reactors, $this->projector, false);
    $continue = $streamEventReactor($this->hub, $streamName, $this->event, $position);

    expect($continue)->toBe($stillRunning);
})
    ->with('projection running')
    ->with('no gap');

test('does not react when gap is found', function (GapType $gapType) {
    $this->event->expects('header')->with(Header::EVENT_TIME)->andReturn($this->eventTime);

    $streamName = 'stream-1';
    $position = 10;

    shouldExpectGap($streamName, $position, $gapType)($this);

    $this->hub->shouldNotReceive('expect')->with(IsUserStateInitialized::class);
    $this->hub->shouldNotReceive('expect')->with(CurrentUserState::class);
    $this->hub->shouldNotReceive('notify')->with(BatchIncremented::class);
    $this->hub->shouldNotReceive('notify')->with(UserStateChanged::class, ['count' => 5]);
    $this->hub->shouldNotReceive('notify')->with(StreamEventAcked::class, $this->event::class);
    $this->hub->shouldNotReceive('notifyWhen');
    $this->hub->shouldNotReceive('trigger');
    $this->hub->shouldNotReceive('expect')->with(IsSprintRunning::class);

    //
    $streamEventReactor = new StreamEventReactor(fn () => null, $this->projector, false);
    $continue = $streamEventReactor($this->hub, $streamName, $this->event, $position);

    expect($continue)->toBeFalse();
})->with('has gap');

test('react on event but does notify of non acked event', function (bool $stillRunning, ?GapType $gapType) {
    $this->event->expects('header')->with(Header::EVENT_TIME)->andReturn($this->eventTime);

    $streamName = 'stream-1';
    $position = 10;
    $userState = ['count' => 0];

    $reactors = function (EventScope $scope): void {
        $scope->userState->increment(value: 20);
    };

    shouldExpectGap($streamName, $position, $gapType)($this);
    shouldInitUserState($userState)($this);

    $this->hub->shouldReceive('notify')->with(BatchIncremented::class);
    $this->hub->shouldReceive('notify')->with(UserStateChanged::class, ['count' => 20]);
    $this->hub->shouldNotReceive('notify')->with(StreamEventAcked::class, $this->event::class);
    $this->hub->shouldReceive('notifyWhen')->twice()
        ->withArgs(function (bool $condition, Closure $callback) {
            if ($condition) {
                $callback($this->hub);
            }

            return true;
        });

    $this->hub->shouldReceive('trigger')
        ->withArgs(function (object $notification) {
            return $notification instanceof ProjectionPersistedWhenThresholdIsReached;
        });

    $this->hub->shouldReceive('expect')->with(IsSprintRunning::class)->andReturn($stillRunning);

    //
    $streamEventReactor = new StreamEventReactor($reactors, $this->projector, false);
    $continue = $streamEventReactor($this->hub, $streamName, $this->event, $position);

    expect($continue)->toBe($stillRunning);
})
    ->with('projection running')
    ->with('no gap');

test('react on acked event but does not notify null user state', function (bool $stillRunning, ?GapType $gapType) {
    $this->event->expects('header')->with(Header::EVENT_TIME)->andReturn($this->eventTime);

    $streamName = 'stream-1';
    $position = 10;

    $reactors = function (EventScope $scope): void {
        $scope->ack($this->event::class);

        expect($scope->userState)->toBeNull();
    };

    shouldExpectGap($streamName, $position, $gapType)($this);
    shouldInitUserState(null)($this);

    $this->hub->shouldReceive('notify')->with(BatchIncremented::class);
    $this->hub->shouldNotReceive('notify')->with(UserStateChanged::class, []);
    $this->hub->shouldReceive('notify')->with(StreamEventAcked::class, $this->event::class);
    $this->hub->shouldReceive('notifyWhen')->twice()
        ->withArgs(function (bool $condition, Closure $callback) {
            if ($condition) {
                $callback($this->hub);
            }

            return true;
        });

    $this->hub->shouldReceive('trigger')
        ->withArgs(function (object $notification) {
            return $notification instanceof ProjectionPersistedWhenThresholdIsReached;
        });

    $this->hub->shouldReceive('expect')->with(IsSprintRunning::class)->andReturn($stillRunning);

    //
    $streamEventReactor = new StreamEventReactor($reactors, $this->projector, false);
    $continue = $streamEventReactor($this->hub, $streamName, $this->event, $position);

    expect($continue)->toBe($stillRunning);
})
    ->with('projection running')
    ->with('no gap');

test('dispatch signal', function () {})->todo();
