<?php

declare(strict_types=1);

namespace Storm\Tests\Unit\Projector\Subscription;

use Mockery\MockInterface;
use Provider\Event\PerformWhenThresholdIsReached;
use Provider\Event\ProjectionClosed;
use Provider\Event\ProjectionDiscarded;
use Provider\Event\ProjectionFreed;
use Provider\Event\ProjectionLockUpdated;
use Provider\Event\ProjectionRestarted;
use Provider\Event\ProjectionRevised;
use Provider\Event\ProjectionRise;
use Provider\Event\ProjectionStatusDisclosed;
use Provider\Event\ProjectionStored;
use Provider\Event\ProjectionSynchronized;
use Provider\Event\StreamEventEmitted;
use Provider\Event\StreamEventLinkedTo;
use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Projection\EmitterProjection;
use Storm\Projector\Projection\PersistentProjection;
use Storm\Projector\Projection\ProviderEventMap;
use Storm\Projector\Projection\QueryProjection;
use Storm\Projector\Projection\ReadModelProjection;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Handler\WhenEventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Handler\WhenStreamEventProcessed;
use Storm\Projector\Workflow\Notification\Handler\WhenWorkflowBegan;
use Storm\Projector\Workflow\Notification\Promise\StreamEventProcessed;
use Storm\Projector\Workflow\Notification\WorkflowBegan;

use function array_keys;

beforeEach(function () {
    $this->hub = mock(NotificationHub::class);
    $this->map = new ProviderEventMap;
    $this->defaultListeners = [
        WorkflowBegan::class => WhenWorkflowBegan::class,
        StreamEventProcessed::class => WhenStreamEventProcessed::class,
        EventStreamDiscovered::class => WhenEventStreamDiscovered::class,
    ];
});

function defaultHooksSubscriptionMap(PersistentProjection&MockInterface $management): array
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
test('subscribe to persistent management', function (PersistentProjection&MockInterface $management) {
    $management->shouldReceive('hub')->andReturn($this->hub);

    $this->hub->expects('addHooks')->withArgs(
        fn (array $hooks) => array_keys($hooks) === array_keys(defaultHooksSubscriptionMap($management))
    );

    $this->hub->expects('addEvents')->with($this->defaultListeners);

    if ($management instanceof EmitterProjection) {
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
    'read model management' => fn () => mock(ReadModelProjection::class),
    'emitter management' => fn () => mock(EmitterProjection::class),
]);

test('subscribe to listener with query management', function () {
    $management = mock(QueryProjection::class);

    $management->shouldReceive('hub')->andReturn($this->hub);

    $this->hub->expects('addHooks')->never();
    $this->hub->expects('addEvents')->with($this->defaultListeners);

    $this->map->subscribeTo($management);
});
