<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Mockery\MockInterface;
use Storm\Contract\Projector\EmitterManagement;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\PersistentManagement;
use Storm\Contract\Projector\QueryManagement;
use Storm\Contract\Projector\ReadModelManagement;
use Storm\Projector\Subscription\PersistentManagementEventMap;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Handler\WhenEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Handler\WhenStreamEventProcessed;
use Storm\Projector\Workflow\Notification\Handler\WhenWorkflowBegan;
use Storm\Projector\Workflow\Notification\Management\PerformWhenThresholdIsReached;
use Storm\Projector\Workflow\Notification\Management\ProjectionClosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionDiscarded;
use Storm\Projector\Workflow\Notification\Management\ProjectionFreed;
use Storm\Projector\Workflow\Notification\Management\ProjectionLockUpdated;
use Storm\Projector\Workflow\Notification\Management\ProjectionRestarted;
use Storm\Projector\Workflow\Notification\Management\ProjectionRevised;
use Storm\Projector\Workflow\Notification\Management\ProjectionRise;
use Storm\Projector\Workflow\Notification\Management\ProjectionStatusDisclosed;
use Storm\Projector\Workflow\Notification\Management\ProjectionStored;
use Storm\Projector\Workflow\Notification\Management\ProjectionSynchronized;
use Storm\Projector\Workflow\Notification\Management\StreamEventEmitted;
use Storm\Projector\Workflow\Notification\Management\StreamEventLinkedTo;
use Storm\Projector\Workflow\Notification\Promise\StreamEventProcessed;
use Storm\Projector\Workflow\Notification\WorkflowBegan;

use function array_keys;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->map = new PersistentManagementEventMap();
    $this->defaultListeners = [
        WorkflowBegan::class => WhenWorkflowBegan::class,
        StreamEventProcessed::class => WhenStreamEventProcessed::class,
        EventStreamDiscovered::class => WhenEventStreamDiscovered::class,
    ];
});

function defaultHooksSubscriptionMap(PersistentManagement&MockInterface $management): array
{
    return [
        ProjectionRise::class => fn () => $management->rise(),
        ProjectionLockUpdated::class => fn () => $management->shouldUpdateLock(),
        ProjectionStored::class => fn () => $management->store(),
        PerformWhenThresholdIsReached::class => fn () => $management->performWhenThresholdIsReached(),
        ProjectionClosed::class => fn () => $management->close(),
        ProjectionRevised::class => fn () => $management->revise(),
        ProjectionDiscarded::class => fn (ProjectionDiscarded $listener) => $management->discard($listener->withEmittedEvents),
        ProjectionFreed::class => fn () => $management->freed(),
        ProjectionRestarted::class => fn () => $management->restart(),
        ProjectionStatusDisclosed::class => fn () => $management->disclose(),
        ProjectionSynchronized::class => fn () => $management->synchronise(),
    ];
}

//fixMe we only test hooks keys but not the values
test('subscribe to persistent management', function (PersistentManagement&MockInterface $management) {
    $management->shouldReceive('hub')->andReturn($this->hub);

    $this->hub->expects('addHooks')->withArgs(
        fn (array $hooks) => array_keys($hooks) === array_keys(defaultHooksSubscriptionMap($management))
    );

    $this->hub->expects('addEvents')->with($this->defaultListeners);

    if ($management instanceof EmitterManagement) {
        $emitterHooks = [
            StreamEventEmitted::class => fn (StreamEventEmitted $listener) => $management->emit($listener->event),
            StreamEventLinkedTo::class => fn (StreamEventLinkedTo $listener) => $management->linkTo($listener->streamName, $listener->event),
        ];

        $this->hub->expects('addHooks')->withArgs(
            fn (array $hooks) => array_keys($hooks) === array_keys($emitterHooks)
        );
    }

    $this->map->subscribeTo($management);
})->with([
    'read model management' => fn () => mock(ReadModelManagement::class),
    'emitter management' => fn () => mock(EmitterManagement::class),
]);

test('subscribe to listener with query management', function () {
    $management = mock(QueryManagement::class);

    $management->shouldReceive('hub')->andReturn($this->hub);

    $this->hub->expects('addHooks')->never();
    $this->hub->expects('addEvents')->with($this->defaultListeners);

    $this->map->subscribeTo($management);
});
