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
use Storm\Projector\Provider\EmitterProvider;
use Storm\Projector\Provider\PersistentProvider;
use Storm\Projector\Provider\ProviderEventMap;
use Storm\Projector\Provider\QueryProvider;
use Storm\Projector\Provider\ReadModelProvider;
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

function defaultHooksSubscriptionMap(PersistentProvider&MockInterface $management): array
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
test('subscribe to persistent management', function (PersistentProvider&MockInterface $management) {
    $management->shouldReceive('hub')->andReturn($this->hub);

    $this->hub->expects('addHooks')->withArgs(
        fn (array $hooks) => array_keys($hooks) === array_keys(defaultHooksSubscriptionMap($management))
    );

    $this->hub->expects('addEvents')->with($this->defaultListeners);

    if ($management instanceof EmitterProvider) {
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
    'read model management' => fn () => mock(ReadModelProvider::class),
    'emitter management' => fn () => mock(EmitterProvider::class),
]);

test('subscribe to listener with query management', function () {
    $management = mock(QueryProvider::class);

    $management->shouldReceive('hub')->andReturn($this->hub);

    $this->hub->expects('addHooks')->never();
    $this->hub->expects('addEvents')->with($this->defaultListeners);

    $this->map->subscribeTo($management);
});
