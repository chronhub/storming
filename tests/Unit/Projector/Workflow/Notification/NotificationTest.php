<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Workflow\Notification;

use Checkpoint\CheckpointRecognition;
use stdClass;
use Storm\Clock\PointInTime;
use Storm\Contract\Projector\ContextReader;
use Storm\Projector\Checkpoint\CheckpointFactory;
use Storm\Projector\Checkpoint\StreamPoint;
use Storm\Projector\Exception\CheckpointViolation;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Stream\Iterator\MergeStreamIterator;
use Storm\Projector\Support\Metrics\AckedMetric;
use Storm\Projector\Support\Metrics\CycleMetric;
use Storm\Projector\Support\Metrics\MainMetric;
use Storm\Projector\Support\Metrics\ProcessedMetric;
use Storm\Projector\Workflow\Component;
use Storm\Projector\Workflow\Component\Computation;
use Storm\Projector\Workflow\Component\EventStreamBatch;
use Storm\Projector\Workflow\Component\EventStreamDiscovery;
use Storm\Projector\Workflow\Component\Sprint;
use Storm\Projector\Workflow\Component\Timer;
use Storm\Projector\Workflow\Component\UserState;
use Storm\Projector\Workflow\Notification\BeforeWorkflowRenewal;
use Storm\Projector\Workflow\Notification\Command\BatchStreamIncrements;
use Storm\Projector\Workflow\Notification\Command\BatchStreamReset;
use Storm\Projector\Workflow\Notification\Command\BatchStreamSet;
use Storm\Projector\Workflow\Notification\Command\BatchStreamSleep;
use Storm\Projector\Workflow\Notification\Command\CheckpointReset;
use Storm\Projector\Workflow\Notification\Command\CheckpointUpdated;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\KeepMainCounterOnStop;
use Storm\Projector\Workflow\Notification\Command\MainCounterReset;
use Storm\Projector\Workflow\Notification\Command\NewEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\NewEventStreamReset;
use Storm\Projector\Workflow\Notification\Command\NoEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\SleepOnGap;
use Storm\Projector\Workflow\Notification\Command\SprintContinue;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;
use Storm\Projector\Workflow\Notification\Command\StatusChanged;
use Storm\Projector\Workflow\Notification\Command\StatusDisclosed;
use Storm\Projector\Workflow\Notification\Command\StreamEventAcked;
use Storm\Projector\Workflow\Notification\Command\StreamEventAckedReset;
use Storm\Projector\Workflow\Notification\Command\StreamProcessed;
use Storm\Projector\Workflow\Notification\Command\TimeReset;
use Storm\Projector\Workflow\Notification\Command\TimeStarted;
use Storm\Projector\Workflow\Notification\Command\UserStateChanged;
use Storm\Projector\Workflow\Notification\Command\UserStateRestored;
use Storm\Projector\Workflow\Notification\Command\WorkflowCycleReset;
use Storm\Projector\Workflow\Notification\GapDetected;
use Storm\Projector\Workflow\Notification\IsSprintTerminated;
use Storm\Projector\Workflow\Notification\Promise\CurrentCheckpoint;
use Storm\Projector\Workflow\Notification\Promise\CurrentElapsedTime;
use Storm\Projector\Workflow\Notification\Promise\CurrentFilteredCheckpoint;
use Storm\Projector\Workflow\Notification\Promise\CurrentMainCount;
use Storm\Projector\Workflow\Notification\Promise\CurrentNewEventStreams;
use Storm\Projector\Workflow\Notification\Promise\CurrentProcessedStream;
use Storm\Projector\Workflow\Notification\Promise\CurrentStartedTime;
use Storm\Projector\Workflow\Notification\Promise\CurrentStatus;
use Storm\Projector\Workflow\Notification\Promise\CurrentTime;
use Storm\Projector\Workflow\Notification\Promise\CurrentUserState;
use Storm\Projector\Workflow\Notification\Promise\CurrentWorkflowCycle;
use Storm\Projector\Workflow\Notification\Promise\GetProjectionReport;
use Storm\Projector\Workflow\Notification\Promise\HasEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Promise\HasGap;
use Storm\Projector\Workflow\Notification\Promise\IsBatchStreamBlank;
use Storm\Projector\Workflow\Notification\Promise\IsBatchStreamLimitReached;
use Storm\Projector\Workflow\Notification\Promise\IsBatchStreamReset;
use Storm\Projector\Workflow\Notification\Promise\IsFirstWorkflowCycle;
use Storm\Projector\Workflow\Notification\Promise\IsSprintDaemonize;
use Storm\Projector\Workflow\Notification\Promise\IsSprintRunning;
use Storm\Projector\Workflow\Notification\Promise\IsTimeStarted;
use Storm\Projector\Workflow\Notification\Promise\IsUserStateInitialized;
use Storm\Projector\Workflow\Notification\Promise\IsWorkflowStarted;
use Storm\Projector\Workflow\Notification\Promise\PullBatchStream;
use Storm\Projector\Workflow\Notification\Promise\StreamEventProcessed;
use Storm\Projector\Workflow\Notification\RecoverableGapDetected;
use Storm\Projector\Workflow\Notification\SprintTerminated;
use Storm\Projector\Workflow\Notification\UnrecoverableGapDetected;
use Storm\Projector\Workflow\Notification\WorkflowBegan;
use Storm\Projector\Workflow\Notification\WorkflowCycleIncremented;
use Storm\Projector\Workflow\Notification\WorkflowStarted;
use Storm\Tests\Stubs\Double\Message\AnotherEvent;
use Storm\Tests\Stubs\Double\Message\SomeEvent;
use Storm\Tests\Stubs\MergeStreamIteratorStub;

use function microtime;

beforeEach(function () {
    $this->subscriptor = mock(Component::class);
    $this->watcherManager = mock(Component::class);

    $this->streamEventWatcher = mock(EventStreamBatch::class);
    $this->recognitionWatcher = mock(CheckpointRecognition::class);
    $this->sprintWatcher = mock(Sprint::class);
    $this->timeWatcher = mock(Timer::class);
    $this->userStateWatcher = mock(UserState::class);
    $this->eventStreamWatcher = mock(EventStreamDiscovery::class);
    $this->reportWatcher = mock(Computation::class);
});

dataset('boolean', [[true], [false]]);

describe('notify stream event', function () {
    test('should sleep', function () {
        $this->subscriptor->expects('batch')->andReturn($this->streamEventWatcher);
        $this->streamEventWatcher->expects('sleep');

        $notification = new BatchStreamSleep;

        $notification($this->subscriptor);
    });
});

describe('notify report processed', function () {
    test('should reset', function () {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);

        $processed = new ProcessedMetric(1000);
        expect($processed->count())->toBe(0);
        $processed->increment();
        expect($processed->count())->toBe(1);

        $this->reportWatcher->expects('processed')->andReturn($processed);

        $notification = new BatchStreamReset;

        $notification($this->subscriptor);

        expect($processed->count())->toBe(0);
    });

    test('check counter is reset', function (bool $isBatchReset) {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);

        $processed = new ProcessedMetric(1000);
        expect($processed->count())->toBe(0);

        if (! $isBatchReset) {
            $processed->increment();
            expect($processed->count())->toBe(1);
        }

        $this->reportWatcher->expects('processed')->andReturn($processed);

        $notification = new IsBatchStreamReset;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($isBatchReset);
    })->with('boolean');

    test('notify batch counter is reached', function (bool $thresholdReached) {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);

        $processed = new ProcessedMetric(2);
        expect($processed->count())->toBe(0);

        if ($thresholdReached) {
            $processed->increment();
            $processed->increment();
            expect($processed->count())->toBe(2);
        }

        $this->reportWatcher->expects('processed')->andReturn($processed);

        $notification = new IsBatchStreamLimitReached;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($thresholdReached);
    })->with('boolean');
});

describe('notify checkpoint', function () {

    test('should insert checkpoint', function (string|PointInTime $eventTime) {
        $this->subscriptor->expects('recognition')->andReturn($this->recognitionWatcher);

        $checkpoint = CheckpointFactory::from('stream1', 1, '2024-07-12T17:38:44.161888', 'createdAt', [], null);

        $this->recognitionWatcher->expects('record')
            ->withArgs(function (StreamPoint $streamPoint) use ($eventTime) {
                return $streamPoint->name === 'stream1'
                    && $streamPoint->position === 1
                    && $streamPoint->eventTime === $eventTime;
            })->andReturn($checkpoint);

        $notification = new StreamEventProcessed('stream1', 1, $eventTime);

        $result = $notification($this->subscriptor);

        expect($result)->toBe($checkpoint);
    })->with([
        ['2024-07-12T17:38:44.161888'],
        [PointInTime::fromString('2024-07-12T17:38:44.161888')],
    ]);

    test('insert checkpoint raise exception when stream position is less than one', function (int $position) {
        $this->watcherManager->shouldNotReceive('recognition');
        $this->recognitionWatcher->shouldNotReceive('record');

        $notification = new StreamEventProcessed('streamName', $position, '2024-07-12T17:38:44.161888');

        try {
            $notification($this->subscriptor);
        } catch (CheckpointViolation $e) {
            expect($e->getMessage())->toBe("Stream position $position must be greater than 0 for stream streamName");
        }
    })->with([[-1], [0]]);

    test('should reset', function () {
        $this->subscriptor->expects('recognition')->andReturn($this->recognitionWatcher);

        $this->recognitionWatcher->expects('resets');

        $notification = new CheckpointReset;

        $notification($this->subscriptor);
    });

    test('should update checkpoint', function () {
        $this->subscriptor->expects('recognition')->andReturn($this->recognitionWatcher);

        $checkpoints = [
            CheckpointFactory::from('stream1', 1, '2024-07-12T17:38:44.161888', 'createdAt', [], null),
            CheckpointFactory::from('stream2', 2, '2024-07-12T17:40:44.161888', 'createdAt', [], null),
        ];

        $this->recognitionWatcher
            ->expects('update')
            ->withArgs(fn (array $expectedCheckpoints) => $checkpoints === $expectedCheckpoints);

        $notification = new CheckpointUpdated($checkpoints);

        $notification($this->subscriptor);
    });

    test('should return current checkpoint', function () {
        $this->subscriptor->expects('recognition')->andReturn($this->recognitionWatcher);

        $checkpoints = [
            CheckpointFactory::from('stream1', 1, '2024-07-12T17:38:44.161888', 'createdAt', [], null),
            CheckpointFactory::from('stream2', 2, '2024-07-12T17:40:44.161888', 'createdAt', [], null),
        ];

        $this->recognitionWatcher->expects('toArray')->andReturn($checkpoints);

        $notification = new CurrentCheckpoint;
        $result = $notification($this->subscriptor);

        expect($result)->toBe($checkpoints);
    });

    test('should return current filtered checkpoint', function () {
        $this->subscriptor->expects('recognition')->andReturn($this->recognitionWatcher);

        $checkpoints = [
            CheckpointFactory::from('stream1', 1, '2024-07-12T17:38:44.161888', 'createdAt', [], null),
            CheckpointFactory::from('stream2', 2, '2024-07-12T17:40:44.161888', 'createdAt', [], null),
        ];

        $this->recognitionWatcher->expects('jsonSerialize')->andReturn($checkpoints);

        $notification = new CurrentFilteredCheckpoint;
        $result = $notification($this->subscriptor);

        expect($result)->toBe($checkpoints);
    });

    test('gap detected notification', function () {
        $notification = new GapDetected('stream1', 1);

        expect($notification->position)->toBe(1)
            ->and($notification->streamName)->toBe('stream1')
            ->and($notification)->not->toBeCallable();
    });

    test('recoverable gap notification', function () {
        $this->subscriptor->shouldNotReceive('watcher');

        $notification = new RecoverableGapDetected('stream1', 1);

        expect($notification->position)->toBe(1)
            ->and($notification->streamName)->toBe('stream1')
            ->and($notification)->not->toBeCallable();
    });

    test('unrecoverable gap notification', function () {
        $this->subscriptor->shouldNotReceive('watcher');

        $notification = new UnrecoverableGapDetected('stream1', 1);

        expect($notification->position)->toBe(1)
            ->and($notification->streamName)->toBe('stream1')
            ->and($notification)->not->toBeCallable();
    });

    test('has gap notification', function (bool $hasGap) {
        $this->subscriptor->expects('recognition')->andReturn($this->recognitionWatcher);

        $this->recognitionWatcher->expects('hasGap')->andReturn($hasGap);

        $notification = new HasGap;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($hasGap);
    })->with('boolean');

    test('sleep on gap', function () {
        $this->subscriptor->expects('recognition')->andReturn($this->recognitionWatcher);

        $this->recognitionWatcher->expects('sleepOnGap');

        $notification = new SleepOnGap;

        $notification($this->subscriptor);
    });
});

describe('notify workflow', function () {
    test('before cycle renewed', function () {
        $notification = new BeforeWorkflowRenewal;

        expect($notification)->not->toBeCallable();
    });

    test('cycle began', function () {
        $notification = new WorkflowBegan;

        expect($notification)->not->toBeCallable();
    });

    test('increment cycle', function () {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);

        $cycle = new CycleMetric;

        expect($cycle->current())->toBe(0);
        $cycle->next();
        $cycle->next();
        expect($cycle->current())->toBe(2);

        $this->reportWatcher->expects('cycle')->andReturn($cycle);

        $notification = new WorkflowCycleIncremented;

        $notification($this->subscriptor);

        expect($cycle->current())->toBe(3);
    });

    test('current cycle', function (int $currentCycle) {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);

        $cycle = new CycleMetric;
        expect($cycle->current())->toBe(0);

        while ($cycle->current() < $currentCycle) {
            $cycle->next();
        }

        $this->reportWatcher->expects('cycle')->andReturn($cycle);

        $notification = new CurrentWorkflowCycle;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($currentCycle);
    })->with([[1], [10]]);

    test('reset cycle', function () {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);

        $cycle = new CycleMetric;
        $this->reportWatcher->expects('cycle')->andReturn($cycle);

        expect($cycle->current())->toBe(0);
        $cycle->next();
        $cycle->next();
        expect($cycle->current())->toBe(2);

        $notification = new WorkflowCycleReset;

        $notification($this->subscriptor);

        expect($cycle->current())->toBe(0);
    });

    test('start cycle', function () {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);

        $cycle = new CycleMetric;
        $this->reportWatcher->expects('cycle')->andReturn($cycle);
        expect($cycle->current())->toBe(0);

        $notification = new WorkflowStarted;

        $notification($this->subscriptor);

        expect($cycle->current())->toBe(1);
    });

    test('check is cycle started', function (bool $isCycleStarted) {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);

        $cycle = new CycleMetric;
        $this->reportWatcher->expects('cycle')->andReturn($cycle);
        expect($cycle->current())->toBe(0);

        if ($isCycleStarted) {
            $cycle->next();
            expect($cycle->current())->toBe(1);
        }

        $notification = new IsWorkflowStarted;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($isCycleStarted);
    })->with('boolean');

    test('check is first cycle', function (bool $isFirstCycle) {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);

        $cycle = new CycleMetric;
        $this->reportWatcher->expects('cycle')->andReturn($cycle);
        expect($cycle->current())->toBe(0);

        if ($isFirstCycle) {
            $cycle->next();
            expect($cycle->current())->toBe(1);
        }

        $notification = new IsFirstWorkflowCycle;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($isFirstCycle);
    })->with('boolean');
});

describe('notify main counter', function () {
    test('current main counter', function (int $currentMainCounter) {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);

        $mainCounter = new MainMetric;
        $this->reportWatcher->expects('main')->andReturn($mainCounter);

        expect($mainCounter->current())->toBe(0);
        while ($mainCounter->current() < $currentMainCounter) {
            $mainCounter->increment();
        }

        $notification = new CurrentMainCount;
        $result = $notification($this->subscriptor);

        expect($result)->toBe($currentMainCounter);
    })->with([[1], [10]]);

    test('keep main counter on stop', function (bool $keepMainCounter) {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);

        $mainCounter = new MainMetric;
        $this->reportWatcher->expects('main')->andReturn($mainCounter);

        expect($mainCounter->isDoNotReset())->toBeFalse();
        $mainCounter->doNotReset($keepMainCounter);

        $notification = new KeepMainCounterOnStop($keepMainCounter);

        $notification($this->subscriptor);

        expect($mainCounter->isDoNotReset())->toBe($keepMainCounter);
    })->with('boolean');

    test('reset main counter', function () {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);

        $mainCounter = new MainMetric;
        $this->reportWatcher->expects('main')->andReturn($mainCounter);

        expect($mainCounter->current())->toBe(0);
        $mainCounter->increment();
        $mainCounter->increment();
        expect($mainCounter->current())->toBe(2);

        $notification = new MainCounterReset;

        $notification($this->subscriptor);

        expect($mainCounter->current())->toBe(0);
    });
});

describe('notify sprint', function () {
    test('is sprint daemon started', function (bool $keepRunning) {
        $this->subscriptor->expects('sprint')->andReturn($this->sprintWatcher);
        $this->sprintWatcher->expects('inBackground')->andReturn($keepRunning);

        $notification = new IsSprintDaemonize;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($keepRunning);
    })->with('boolean');

    test('is sprint running', function (bool $isSprintRunning) {
        $this->subscriptor->expects('sprint')->andReturn($this->sprintWatcher);
        $this->sprintWatcher->expects('inProgress')->andReturn($isSprintRunning);

        $notification = new IsSprintRunning;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($isSprintRunning);
    })->with('boolean');

    test('continue sprint', function () {
        $this->subscriptor->expects('sprint')->andReturn($this->sprintWatcher);
        $this->sprintWatcher->expects('continue');

        $notification = new SprintContinue;

        $notification($this->subscriptor);
    });

    test('stop sprint', function () {
        $this->subscriptor->expects('sprint')->andReturn($this->sprintWatcher);
        $this->sprintWatcher->expects('halt');

        $notification = new SprintStopped;

        $notification($this->subscriptor);
    });

    test('sprint terminated', function () {
        $this->subscriptor->shouldNotReceive('watcher');

        $notification = new SprintTerminated;

        expect($notification)->not->toBeCallable();
    });

    test('is sprint terminated', function ($inBackground, $inProgress) {
        $this->subscriptor->shouldReceive('sprint')->andReturn($this->sprintWatcher);

        $this->sprintWatcher->shouldReceive('inBackground')->andReturn($inBackground);
        $this->sprintWatcher->shouldReceive('inProgress')->andReturn($inProgress);

        $notification = new IsSprintTerminated;

        $result = $notification($this->subscriptor);

        ! $inBackground || ! $inProgress
            ? expect($result)->toBeTrue()
            : expect($result)->toBeFalse();
    })->with('boolean', 'boolean');
});

describe('notify timer', function () {

    test('time reset', function () {
        $this->subscriptor->expects('time')->andReturn($this->timeWatcher);
        $this->timeWatcher->expects('reset');

        $notification = new TimeReset;

        $notification($this->subscriptor);
    });

    test('time started', function () {
        $this->subscriptor->expects('time')->andReturn($this->timeWatcher);
        $this->timeWatcher->expects('start');

        $notification = new TimeStarted;

        $notification($this->subscriptor);
    });

    test('is time started', function (bool $isTimeStarted) {
        $this->subscriptor->expects('time')->andReturn($this->timeWatcher);
        $this->timeWatcher->expects('isStarted')->andReturn($isTimeStarted);

        $notification = new IsTimeStarted;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($isTimeStarted);
    })->with('boolean');

    test('get started time', function () {
        $startedTime = (int) microtime();

        $this->subscriptor->expects('time')->andReturn($this->timeWatcher);
        $this->timeWatcher->expects('getStartedTime')->andReturn($startedTime);

        $notification = new CurrentStartedTime;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($startedTime);
    });

    test('get elapsed time', function (int $elapsedTime) {
        $this->subscriptor->expects('time')->andReturn($this->timeWatcher);
        $this->timeWatcher->expects('getElapsedTime')->andReturn($elapsedTime);

        $notification = new CurrentElapsedTime;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($elapsedTime);
    })->with([[1], [10]]);

    test('get current time', function () {
        $currentTime = (int) microtime();
        $this->subscriptor->expects('time')->andReturn($this->timeWatcher);
        $this->timeWatcher->expects('getCurrentTime')->andReturn($currentTime);

        $notification = new CurrentTime;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($currentTime);
    });
});

describe('notify status', function () {
    test('current status', function (ProjectionStatus $status) {
        $this->subscriptor->expects('currentStatus')->andReturn($status);

        $notification = new CurrentStatus;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($status);
    })->with('projection status');

    test('status changed', function (ProjectionStatus $newStatus, ProjectionStatus $oldStatus) {
        $this->subscriptor->expects('setStatus')->with($newStatus);

        $notification = new StatusChanged($newStatus, $oldStatus);

        expect($notification->newStatus)->toBe($newStatus)
            ->and($notification->oldStatus)->toBe($oldStatus);

        $notification($this->subscriptor);
    })->with('projection status', 'projection status');

    test('status disclosed', function (ProjectionStatus $newStatus, ProjectionStatus $oldStatus) {
        $this->subscriptor->expects('setStatus')->with($newStatus);

        $notification = new StatusDisclosed($newStatus, $oldStatus);

        expect($notification->newStatus)->toBe($newStatus)
            ->and($notification->oldStatus)->toBe($oldStatus);

        $notification($this->subscriptor);
    })->with('projection status', 'projection status');
});

describe('notify user state', function () {
    test('current user state', function (array $userState) {
        $this->subscriptor->expects('userState')->andReturn($this->userStateWatcher);
        $this->userStateWatcher->expects('get')->andReturn($userState);

        $notification = new CurrentUserState;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($userState);
    })->with([[['foo' => 'bar']], [['bar' => 'baz']]]);

    test('is user state initialized', function (bool $isInitialized) {
        $context = mock(ContextReader::class);
        $this->subscriptor->expects('getContext')->andReturn($context);

        $return = $isInitialized ? fn () => [] : null;
        $context->expects('userState')->andReturn($return);

        $notification = new IsUserStateInitialized;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($isInitialized);
    })->with('boolean');

    test('user state changed', function (array $newState) {
        $this->subscriptor->expects('userState')->andReturn($this->userStateWatcher);
        $this->userStateWatcher->expects('put')->with($newState);

        $notification = new UserStateChanged($newState);

        expect($notification->userState)->toBe($newState);

        $notification($this->subscriptor);
    })->with([[[]], [['foo' => 'bar']], [['bar' => 'baz']]]);

    test('user state restored', function (bool $isInitialized) {
        $context = mock(ContextReader::class);
        $this->subscriptor->expects('getContext')->andReturn($context);
        $this->subscriptor->expects('userState')->andReturn($this->userStateWatcher);

        $return = $isInitialized ? fn () => [] : null;
        $context->expects('userState')->andReturn($return);
        $this->userStateWatcher->expects('init')->with($return);

        $notification = new UserStateRestored;
        $notification($this->subscriptor);
    })->with('boolean');
});

describe('notify acked event', function () {
    test('stream event acked', function (string $event) {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);
        $ackedCounter = new AckedMetric;
        $this->reportWatcher->expects('acked')->andReturn($ackedCounter);

        $notification = new StreamEventAcked($event);

        $notification($this->subscriptor);

        expect($ackedCounter->getEvents())->toBe([$event]);
    })->with([[SomeEvent::class], [stdClass::class]]);

    test('reset acked event', function () {
        $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);
        $ackedCounter = new AckedMetric;
        $this->reportWatcher->expects('acked')->andReturn($ackedCounter);

        $ackedCounter->increment(SomeEvent::class);
        $ackedCounter->increment(AnotherEvent::class);

        expect($ackedCounter->getEvents())->toBe([SomeEvent::class, AnotherEvent::class]);

        $notification = new StreamEventAckedReset;

        $notification($this->subscriptor);

        expect($ackedCounter->getEvents())->toBe([]);
    });
});

describe('interact with subscriptor', function () {
    test('get current processed stream', function (string $streamName) {
        $this->subscriptor->expects('getProcessedStream')->andReturn($streamName);

        $notification = new CurrentProcessedStream;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($streamName);
    })->with([['stream1'], ['stream2']]);

    test('set processed stream', function (string $streamName) {
        $this->subscriptor->expects('setProcessedStream')->with($streamName);

        $notification = new StreamProcessed($streamName);

        $notification($this->subscriptor);
    })->with([['stream1'], ['stream2']]);

    test('discover steams', function () {
        $context = mock(ContextReader::class);

        $query = fn () => ['stream1'];
        $context->expects('query')->andReturn($query);
        $this->subscriptor->expects('getContext')->andReturn($context);
        $this->subscriptor->expects('discovery')->andReturn($this->eventStreamWatcher);

        $this->eventStreamWatcher->expects('discover')->with($query)->andReturn(['stream1']);
        $notification = new EventStreamDiscovered;

        $this->subscriptor->expects('recognition')->andReturn($this->recognitionWatcher);
        $this->recognitionWatcher->expects('track')->with('stream1');

        $notification($this->subscriptor);
    });

    test('pull stream iterator', function (?MergeStreamIterator $iterator) {
        $this->subscriptor->expects('batch')->andReturn($this->streamEventWatcher);
        $this->streamEventWatcher->expects('pull')->andReturn($iterator);

        $notification = new PullBatchStream;
        $result = $notification($this->subscriptor);

        expect($result)->toBe($iterator);
    })->with([[null], [(new MergeStreamIteratorStub)->getMergeStreams()]]);

    test('set stream iterator', function (?MergeStreamIterator $iterator) {
        $this->subscriptor->expects('batch')->andReturn($this->streamEventWatcher);
        $this->streamEventWatcher->expects('set')->with($iterator);

        $notification = new BatchStreamSet($iterator);

        $notification($this->subscriptor);
    })->with([[null], [(new MergeStreamIteratorStub)->getMergeStreams()]]);
});

describe('notify stream discovery', function () {
    test('get new event streams', function (array $streams) {
        $this->subscriptor->expects('discovery')->andReturn($this->eventStreamWatcher);
        $this->eventStreamWatcher->expects('newEventStreams')->andReturn($streams);

        $notification = new CurrentNewEventStreams;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($streams);
    })->with([[['stream1']], [['stream2', 'stream3']]]);

    test('no event stream discovered', function () {
        $notification = new NoEventStreamDiscovered;

        expect($notification)->not->toBeCallable();
    });

    test('has event stream discovered', function (bool $hasEventStreams) {
        $this->subscriptor->expects('discovery')->andReturn($this->eventStreamWatcher);
        $this->eventStreamWatcher->expects('hasEventStreams')->andReturn($hasEventStreams);

        $notification = new HasEventStreamDiscovered;

        $result = $notification($this->subscriptor);

        expect($result)->toBe($hasEventStreams);
    })->with('boolean');

    test('notify new event stream discovered', function (string $eventStream) {
        $this->subscriptor->shouldNotReceive('watcher');
        $notification = new NewEventStreamDiscovered($eventStream);

        expect($notification->eventStream)->toBe($eventStream)
            ->and($notification)->not->toBeCallable();
    })->with([['stream1'], ['stream2']]);

    test('reset new event streams discovered', function () {
        $this->subscriptor->expects('discovery')->andReturn($this->eventStreamWatcher);
        $this->eventStreamWatcher->expects('resetNewEventStreams');

        $notification = new NewEventStreamReset;

        $notification($this->subscriptor);
    });
});

test('notify is process blank', function (bool $isBatchCounterReset, bool $hasAckedEvents) {
    $this->subscriptor->shouldReceive('compute')->andReturn($this->reportWatcher);

    $processedCounter = new ProcessedMetric(1000);
    $this->reportWatcher->expects('processed')->andReturn($processedCounter);
    $ackedCounter = new AckedMetric;
    $this->reportWatcher->shouldReceive('acked')->andReturn($ackedCounter);

    if (! $isBatchCounterReset) {
        $processedCounter->increment();
    }

    if ($hasAckedEvents) {
        $ackedCounter->increment(AnotherEvent::class);
    }

    $notification = new IsBatchStreamBlank;
    $result = $notification($this->subscriptor);

    ! $isBatchCounterReset
        ? expect($result)->toBeFalse()
        : expect($result)->toBe(! $hasAckedEvents);
})->with('boolean', 'boolean');

test('notify batch incremented', function () {
    $this->subscriptor->shouldReceive('compute')->andReturn($this->reportWatcher);

    $processedCounter = new ProcessedMetric(1000);
    $this->reportWatcher->expects('processed')->andReturn($processedCounter);
    $mainCounter = new MainMetric;
    $this->reportWatcher->expects('main')->andReturn($mainCounter);

    expect($mainCounter->current())->toBe(0)
        ->and($processedCounter->count())->toBe(0);

    $notification = new BatchStreamIncrements;
    $notification($this->subscriptor);

    expect($mainCounter->current())->toBe(1)
        ->and($processedCounter->count())->toBe(1);
});

test('get projection report', function (array $report) {
    $this->subscriptor->expects('compute')->andReturn($this->reportWatcher);
    $this->reportWatcher->expects('report')->andReturn($report);

    $notification = new GetProjectionReport;
    $result = $notification($this->subscriptor);

    expect($result)->toBe($report);
})->with([[['foo' => 'bar']], [['bar' => 'baz']]]);
