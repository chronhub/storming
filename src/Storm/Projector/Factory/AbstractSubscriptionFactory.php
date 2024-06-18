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
use Storm\Contract\Projector\Management;
use Storm\Contract\Projector\NotificationHub;
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
use Storm\Projector\Checkpoint\CheckpointStore;
use Storm\Projector\Checkpoint\GapDetector;
use Storm\Projector\Checkpoint\GapRules;
use Storm\Projector\Checkpoint\NoopGapDetector;
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
use Storm\Projector\Subscription\HubManager;
use Storm\Projector\Subscription\QueryingManagement;
use Storm\Projector\Subscription\QuerySubscription;
use Storm\Projector\Subscription\ReadingModelManagement;
use Storm\Projector\Subscription\ReadModelSubscription;
use Storm\Projector\Subscription\SubscriptionManager;
use Storm\Projector\Subscription\SubscriptionMap;
use Storm\Projector\Workflow\DefaultContext;
use Storm\Projector\Workflow\EmittedStream;
use Storm\Projector\Workflow\InMemoryEmittedStreams;
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
        $subscriptor = $this->buildSubscription($option, false);
        $hub = $this->createNotificationManager($subscriptor);
        $management = new QueryingManagement($hub);

        $this->subscribeToMap($management);

        $activities = new QueryActivityFactory($this->chronicler);
        $scope = new QueryAccess($hub, $this->clock);

        return new QuerySubscription($subscriptor, $management, $activities, $scope);
    }

    public function createEmitterSubscription(string $streamName, ProjectionOption $option): EmitterSubscriber
    {
        $subscriptor = $this->buildSubscription($option, true);
        $hub = $this->createNotificationManager($subscriptor);

        $management = new EmittingManagement(
            $hub,
            $this->chronicler,
            $this->createProjectionRepository($streamName, $option),
            $this->getSnapshotRepository(),
            $this->createStreamCache($option),
            new EmittedStream(),
        );

        $this->subscribeToMap($management);

        return new EmitterSubscription(
            $subscriptor,
            $management,
            new PersistentActivityFactory($this->chronicler),
            new EmitterAccess($hub, $this->clock)
        );
    }

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, ProjectionOption $option): ReadModelSubscriber
    {
        $subscriptor = $this->buildSubscription($option, true);
        $hub = $this->createNotificationManager($subscriptor);
        $projectionRepository = $this->createProjectionRepository($streamName, $option);
        $snapshotRepository = $this->getSnapshotRepository();

        $management = new ReadingModelManagement($hub, $projectionRepository, $snapshotRepository, $readModel);

        $this->subscribeToMap($management);

        return new ReadModelSubscription(
            $subscriptor,
            $management,
            new PersistentActivityFactory($this->chronicler),
            new ReadModelAccess($hub, $readModel, $this->clock)
        );
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

    protected function buildSubscription(ProjectionOption $option, bool $detectGap): Subscriptor
    {
        return new SubscriptionManager(
            $this->createCheckpointRecognition($option, $detectGap),
            $this->clock,
            $option,
            $this->createWatcherManager($option),
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
        $gapDetector = $detectGap
            ? new GapDetector($option->getRetries())
            : new NoopGapDetector();

        // fixMe add boolean to enable/disable saving gaps remotely or in option

        return new CheckpointStore($gapDetector, new GapRules(), $this->clock);
    }

    protected function createStreamCache(ProjectionOption $option): EmittedStreamCache
    {
        return new InMemoryEmittedStreams($option->getCacheSize());
    }

    protected function createDispatcherRepository(ProjectionRepository $projectionRepository): EventDispatcherRepository
    {
        return new EventDispatcherRepository($projectionRepository, $this->dispatcher);
    }

    protected function createWatcherManager(ProjectionOption $option): WatcherManager
    {
        return new WatcherManager($option, $this->eventStreamProvider, $this->clock);
    }

    protected function createNotificationManager(Subscriptor $subscriptor): NotificationHub
    {
        return new HubManager($subscriptor);
    }

    protected function subscribeToMap(Management $management): void
    {
        $map = new SubscriptionMap();

        $map->subscribeTo($management);
    }
}
