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
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\Subscriber;
use Storm\Contract\Projector\SubscriptionFactory;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Repository\EventDispatcherRepository;
use Storm\Projector\Repository\LockManager;
use Storm\Projector\Scope\EmitterAccess;
use Storm\Projector\Scope\QueryAccess;
use Storm\Projector\Scope\ReadModelAccess;
use Storm\Projector\Subscription\EmittingManagement;
use Storm\Projector\Subscription\GenericSubscription;
use Storm\Projector\Subscription\ManagementEventMap;
use Storm\Projector\Subscription\QueryingManagement;
use Storm\Projector\Subscription\ReadingModelManagement;
use Storm\Projector\Workflow\EmittedStream;
use Storm\Projector\Workflow\InMemoryEmittedStreams;
use Storm\Projector\Workflow\Stage;
use Storm\Projector\Workflow\WorkflowContext;

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

    public function createQuerySubscription(ProjectionOption $option): Subscriber
    {
        $workflowContext = $this->createWorkflowContext($option);

        $projectorScope = new QueryAccess($workflowContext, $this->clock);
        $activities = new QueryActivityFactory(
            $this->chronicler, $projectorScope, $option, $this->clock
        );

        $management = new QueryingManagement($workflowContext);
        $this->subscribeToMap($management, $workflowContext);

        return new GenericSubscription($workflowContext, $activities, new Stage());
    }

    public function createEmitterSubscription(string $streamName, ProjectionOption $option): Subscriber
    {
        $workflowContext = $this->createWorkflowContext($option);

        $management = new EmittingManagement(
            $workflowContext,
            $this->chronicler,
            $this->createProjectionRepository($streamName, $option),
            $this->createStreamCache($option),
            new EmittedStream(),
            $option->getSleepEmitterOnFirstCommit()
        );

        $this->subscribeToMap($management, $workflowContext);

        $projectorScope = new EmitterAccess($workflowContext, $this->clock);
        $activities = new PersistentActivityFactory($this->chronicler, $projectorScope, $option, $this->clock);

        return new GenericSubscription($workflowContext, $activities, new Stage());
    }

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, ProjectionOption $option): Subscriber
    {
        $workflowContext = $this->createWorkflowContext($option);

        $projectionRepository = $this->createProjectionRepository($streamName, $option);
        $management = new ReadingModelManagement(
            $workflowContext,
            $projectionRepository,
            $readModel
        );
        $this->subscribeToMap($management, $workflowContext);

        $projectorScope = new ReadModelAccess($workflowContext, $readModel, $this->clock);
        $activities = new PersistentActivityFactory($this->chronicler, $projectorScope, $option, $this->clock);

        return new GenericSubscription($workflowContext, $activities, new Stage());
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
    abstract protected function createProjectionRepository(string $streamName, ProjectionOption $options): ProjectionRepository;

    protected function createWorkflowContext(ProjectionOption $option): WorkflowContext
    {
        $agentManager = new AgentProvider($option, $this->eventStreamProvider, $this->clock);

        return new WorkflowContext($agentManager);
    }

    protected function createLockManager(ProjectionOption $option): LockManager
    {
        return new LockManager($this->clock, $option->getTimeout(), $option->getLockout());
    }

    protected function createStreamCache(ProjectionOption $option): EmittedStreamCache
    {
        return new InMemoryEmittedStreams($option->getCacheSize());
    }

    protected function createDispatcherRepository(ProjectionRepository $projectionRepository): EventDispatcherRepository
    {
        return new EventDispatcherRepository($projectionRepository, $this->dispatcher);
    }

    protected function subscribeToMap(Management $management, WorkflowContext $workflowContext): void
    {
        $map = new ManagementEventMap();

        $map->subscribeTo($management, $workflowContext);
    }
}
