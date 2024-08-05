<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Illuminate\Contracts\Events\Dispatcher;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\ChroniclerDecorator;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\EmittedStreamCache;
use Storm\Contract\Projector\Management;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\Repository;
use Storm\Contract\Projector\SubscriptionFactory;
use Storm\Contract\Projector\Subscriptor;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Repository\EventRepository;
use Storm\Projector\Repository\LockManager;
use Storm\Projector\Subscription\EmittingManagement;
use Storm\Projector\Subscription\GenericSubscription;
use Storm\Projector\Subscription\ManagementEventMap;
use Storm\Projector\Subscription\QueryingManagement;
use Storm\Projector\Subscription\ReadingModelManagement;
use Storm\Projector\Workflow\Component;
use Storm\Projector\Workflow\EmittedStream;
use Storm\Projector\Workflow\InMemoryEmittedStreams;
use Storm\Projector\Workflow\Process;

abstract class AbstractSubscriptionFactory implements SubscriptionFactory
{
    protected Chronicler $chronicler;

    public function __construct(
        Chronicler $chronicler,
        protected readonly ProjectionProvider $projectionProvider,
        protected readonly EventStreamProvider $eventStreamProvider,
        protected readonly SystemClock $clock,
        protected readonly SymfonySerializer $serializer,
        protected readonly Dispatcher $dispatcher
    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        $this->chronicler = $chronicler;
    }

    public function createQuerySubscription(ProjectionOption $options): Subscriptor
    {
        $process = $this->createProcessManager($options);
        $activities = new QueryActivityFactory($this->chronicler, $options, $this->clock);

        $management = new QueryingManagement($process);
        $this->subscribeToMap($management, $process);

        return new GenericSubscription($process, $activities);
    }

    public function createEmitterSubscription(string $streamName, ProjectionOption $options): Subscriptor
    {
        $process = $this->createProcessManager($options);

        $management = new EmittingManagement(
            $process,
            $this->chronicler,
            $this->createRepository($streamName, $options),
            $this->createStreamCache($options),
            new EmittedStream(),
            $options->getSleepEmitterOnFirstCommit()
        );

        $this->subscribeToMap($management, $process);

        $activities = new EmitterActivityFactory($this->chronicler, $options, $this->clock);

        return new GenericSubscription($process, $activities);
    }

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, ProjectionOption $options): Subscriptor
    {
        $process = $this->createProcessManager($options);

        $projectionRepository = $this->createRepository($streamName, $options);

        $management = new ReadingModelManagement($process, $projectionRepository, $readModel);
        $this->subscribeToMap($management, $process);

        $activities = new ReadModelActivityFactory($this->chronicler, $options, $this->clock, $readModel);

        return new GenericSubscription($process, $activities);
    }

    public function getProjectionProvider(): ProjectionProvider
    {
        return $this->projectionProvider;
    }

    public function getSerializer(): SymfonySerializer
    {
        return $this->serializer;
    }

    /**
     * Decorate the projection repository with an event dispatcher.
     */
    abstract protected function useEvents(bool $useEvents): void;

    /**
     * Create the projection repository.
     */
    abstract protected function createRepository(string $streamName, ProjectionOption $options): Repository;

    protected function createProcessManager(ProjectionOption $options): Process
    {
        $component = new Component($options, $this->eventStreamProvider, $this->clock);

        return new Process($component);
    }

    protected function createLockManager(ProjectionOption $options): LockManager
    {
        return new LockManager($this->clock, $options->getTimeout(), $options->getLockout());
    }

    protected function createStreamCache(ProjectionOption $options): EmittedStreamCache
    {
        return new InMemoryEmittedStreams($options->getCacheSize());
    }

    protected function createDispatcherRepository(Repository $projectionRepository): EventRepository
    {
        return new EventRepository($projectionRepository, $this->dispatcher);
    }

    protected function subscribeToMap(Management $management, Process $process): void
    {
        $map = new ManagementEventMap();

        $map->subscribeTo($management, $process);
    }
}
