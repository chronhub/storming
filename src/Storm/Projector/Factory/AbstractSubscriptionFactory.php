<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Illuminate\Contracts\Events\Dispatcher;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\ChroniclerDecorator;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\CheckpointRecognition;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\EmittedStreamCache;
use Storm\Contract\Projector\EmitterSubscriber;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectionQueryScope;
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Contract\Projector\QuerySubscriber;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelSubscriber;
use Storm\Contract\Projector\SnapshotRepository;
use Storm\Contract\Projector\SubscriptionFactory;
use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Checkpoint\CheckpointCollection;
use Storm\Projector\Checkpoint\CheckpointInMemory;
use Storm\Projector\Checkpoint\CheckpointManager;
use Storm\Projector\Checkpoint\GapDetector;
use Storm\Projector\Options\ProjectionOptionResolver;
use Storm\Projector\Repository\Checkpoint\InMemorySnapshotProvider;
use Storm\Projector\Repository\Checkpoint\SnapshotCheckpointRepository;
use Storm\Projector\Repository\EventDispatcherRepository;
use Storm\Projector\Repository\LockManager;
use Storm\Projector\Scope\EmitterAccess;
use Storm\Projector\Scope\QueryAccess;
use Storm\Projector\Scope\ReadModelAccess;
use Storm\Projector\Subscription\EmitterSubscription;
use Storm\Projector\Subscription\EmittingManagement;
use Storm\Projector\Subscription\HookHandler;
use Storm\Projector\Subscription\HubManager;
use Storm\Projector\Subscription\ListenerHandler;
use Storm\Projector\Subscription\QueryingManagement;
use Storm\Projector\Subscription\QuerySubscription;
use Storm\Projector\Subscription\ReadingModelManagement;
use Storm\Projector\Subscription\ReadModelSubscription;
use Storm\Projector\Subscription\SubscriptionManager;
use Storm\Projector\Support\Token\ConsumeWithSleepToken;
use Storm\Projector\Workflow\DefaultContext;
use Storm\Projector\Workflow\EmittedStream;
use Storm\Projector\Workflow\InMemoryEmittedStreams;
use Storm\Projector\Workflow\Timer;
use Storm\Projector\Workflow\Watcher\AckedStreamWatcher;
use Storm\Projector\Workflow\Watcher\BatchCounterWatcher;
use Storm\Projector\Workflow\Watcher\BatchStreamWatcher;
use Storm\Projector\Workflow\Watcher\CycleWatcher;
use Storm\Projector\Workflow\Watcher\EventStreamWatcher;
use Storm\Projector\Workflow\Watcher\MasterEventCounterWatcher;
use Storm\Projector\Workflow\Watcher\SnapshotWatcher;
use Storm\Projector\Workflow\Watcher\SprintWatcher;
use Storm\Projector\Workflow\Watcher\StopWatcher;
use Storm\Projector\Workflow\Watcher\TimeWatcher;
use Storm\Projector\Workflow\Watcher\UserStateWatcher;
use Storm\Projector\Workflow\Watcher\WatcherManager;
use Symfony\Component\Serializer\Serializer;

abstract class AbstractSubscriptionFactory implements SubscriptionFactory
{
    protected Chronicler $chronicler;

    public function __construct(
        Chronicler $chronicler,
        protected readonly ProjectionProvider $projectionProvider,
        protected readonly EventStreamProvider $eventStreamProvider,
        protected readonly SystemClock $clock,
        protected readonly Serializer $serializer,
        protected readonly Dispatcher $dispatcher,
        protected readonly ?ProjectionQueryScope $queryScope = null,
        protected readonly ProjectionOption|array $options = [],
    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        $this->chronicler = $chronicler;
    }

    public function createQuerySubscription(ProjectionOption $option): QuerySubscriber
    {
        $subscriptor = $this->createSubscriptor($option, false);
        $notification = new HubManager($subscriptor);
        ListenerHandler::listen($notification);

        $activities = new QueryActivityFactory($this->chronicler);
        $scope = new QueryAccess($notification, $this->clock);

        return new QuerySubscription($subscriptor, new QueryingManagement($notification), $activities, $scope);
    }

    public function createEmitterSubscription(string $streamName, ProjectionOption $option): EmitterSubscriber
    {
        $subscriptor = $this->createSubscriptor($option, true);
        $notification = new HubManager($subscriptor);
        ListenerHandler::listen($notification);

        $management = new EmittingManagement(
            $notification,
            $this->chronicler,
            $this->createProjectionRepository($streamName, $option),
            $this->getSnapshotRepository(),
            $this->createStreamCache($option),
            new EmittedStream(),
        );

        HookHandler::subscribe($notification, $management);

        $activities = new PersistentActivityFactory($this->chronicler);
        $scope = new EmitterAccess($notification, $this->clock);

        return new EmitterSubscription($subscriptor, $management, $activities, $scope);
    }

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, ProjectionOption $option): ReadModelSubscriber
    {
        $subscriptor = $this->createSubscriptor($option, true);
        $notification = new HubManager($subscriptor);
        ListenerHandler::listen($notification);

        $projectionRepository = $this->createProjectionRepository($streamName, $option);
        $snapshotRepository = $this->getSnapshotRepository();

        $management = new ReadingModelManagement($notification, $projectionRepository, $snapshotRepository, $readModel);
        HookHandler::subscribe($notification, $management);

        $activities = new PersistentActivityFactory($this->chronicler);
        $scope = new ReadModelAccess($notification, $readModel, $this->clock);

        return new ReadModelSubscription($subscriptor, $management, $activities, $scope);
    }

    public function createOption(array $options = []): ProjectionOption
    {
        $resolver = new ProjectionOptionResolver($this->options);

        return $resolver($options);
    }

    public function createContextBuilder(): ContextReader
    {
        return new DefaultContext();
    }

    public function getProjectionProvider(): ProjectionProvider
    {
        return $this->projectionProvider;
    }

    public function getSerializer(): Serializer
    {
        return $this->serializer;
    }

    public function getQueryScope(): ?ProjectionQueryScope
    {
        return $this->queryScope;
    }

    abstract protected function useEvents(bool $useEvents): void;

    abstract protected function createProjectionRepository(string $streamName, ProjectionOption $options): ProjectionRepository;

    protected function createSubscriptor(ProjectionOption $option, bool $detectGap): Subscriptor
    {
        return new SubscriptionManager(
            $this->createCheckpointRecognition($option, $detectGap),
            $this->clock,
            $option,
            $this->createMonitorManager($option),
        );
    }

    // todo:
    //  - snapshot provider in constructor
    //  - we snapshot per stream name but we could also work per projection name, and insert in batch
    //  - should not support fromAll (option enableSnapshot)
    protected function getSnapshotRepository(): SnapshotRepository
    {
        return new SnapshotCheckpointRepository(new InMemorySnapshotProvider());
    }

    protected function createLockManager(ProjectionOption $option): LockManager
    {
        return new LockManager($this->clock, $option->getTimeout(), $option->getLockout());
    }

    protected function createCheckpointRecognition(ProjectionOption $option, bool $detectGap): CheckpointRecognition
    {
        $checkpoints = new CheckpointCollection($this->clock);

        if ($detectGap) {
            return new CheckpointManager(
                $checkpoints,
                new GapDetector($option->getRetries())
            );
        }

        return new CheckpointInMemory($checkpoints);
    }

    protected function createStreamCache(ProjectionOption $option): EmittedStreamCache
    {
        return new InMemoryEmittedStreams($option->getCacheSize());
    }

    protected function createDispatcherRepository(ProjectionRepository $projectionRepository): EventDispatcherRepository
    {
        return new EventDispatcherRepository($projectionRepository, $this->dispatcher);
    }

    protected function createMonitorManager(ProjectionOption $option): WatcherManager
    {
        return new WatcherManager(
            new CycleWatcher(),
            new SprintWatcher(),
            new UserStateWatcher(),
            new EventStreamWatcher($this->eventStreamProvider),
            new BatchCounterWatcher($option->getBlockSize()),
            new AckedStreamWatcher(),
            $this->batchStreamWatcher($option),
            new TimeWatcher(new Timer($this->clock)),
            new StopWatcher(),
            new MasterEventCounterWatcher(),
            $this->snapshotWatcher($option),
        );
    }

    protected function batchStreamWatcher(ProjectionOption $option): BatchStreamWatcher
    {
        [$capacity, $rate] = $option->getSleep();

        $bucket = new ConsumeWithSleepToken($capacity, $rate);

        return new BatchStreamWatcher($bucket);
    }

    protected function snapshotWatcher(ProjectionOption $option): SnapshotWatcher
    {
        $interval = $option->getSnapshotInterval();

        return new SnapshotWatcher($this->clock, $interval['position'], $interval['time'], $interval['usleep']);
    }
}
