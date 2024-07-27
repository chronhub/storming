<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Factory;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\Dispatcher as IlluminateEventDispatcher;
use Storm\Chronicler\InMemory\InMemoryEventStore;
use Storm\Chronicler\InMemory\InMemoryEventStreamProvider;
use Storm\Clock\Clock;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\InMemoryChronicler;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectionQueryScope;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\ProjectorMonitorInterface;
use Storm\Contract\Projector\SubscriptionFactory;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Factory\InMemorySubscriptionFactory;
use Storm\Projector\Filter\InMemoryQueryScope;
use Storm\Projector\Options\InMemoryOption;
use Storm\Projector\ProjectorManager;
use Storm\Projector\Repository\InMemoryProjectionProvider;
use Storm\Serializer\JsonSerializerFactory;

class InMemoryTestingFactory
{
    public ?InMemoryChronicler $chronicler = null;

    public ?EventStreamProvider $eventStreamProvider = null;

    public ?ProjectionProvider $projectionProvider = null;

    public ?SystemClock $clock = null;

    public ?Dispatcher $dispatcher = null;

    public ?SymfonySerializer $serializer = null;

    public ?ProjectionOption $projectionOption = null;

    //fixme should be protected and called from the projector manager
    // it may be set before calling the projector manager
    public ?ProjectionQueryScope $queryScope = null;

    public ?ProjectorManagerInterface $projectorManager = null;

    protected ?SubscriptionFactory $subscriptionFactory = null;

    protected ?ProjectorMonitorInterface $monitor = null;

    public function createEventStore(): InMemoryChronicler
    {
        $this->setupEventStore();

        return $this->chronicler;
    }

    public function createProjectorManager(): ProjectorManagerInterface
    {
        if ($this->projectorManager) {
            return $this->projectorManager;
        }

        $this->setupProjectionOption();
        $this->setupQueryScope();

        $this->projectorManager = new ProjectorManager(
            $this->createSubscriptionFactory(),
            $this->projectionOption,
            $this->queryScope
        );

        return $this->projectorManager;
    }

    public function createSubscriptionFactory(): SubscriptionFactory
    {
        if ($this->subscriptionFactory) {
            return $this->subscriptionFactory;
        }

        $this->setupEventStore();
        $this->setupClock();
        $this->setupProjectionProvider();
        $this->setupEventStreamProvider();
        $this->setupSerializer();
        $this->setupDispatcher();

        return $this->subscriptionFactory = new InMemorySubscriptionFactory(
            $this->chronicler,
            $this->projectionProvider,
            $this->eventStreamProvider,
            $this->clock,
            $this->serializer,
            $this->dispatcher,
        );
    }

    public function monitor(): ProjectorMonitorInterface
    {
        return $this->monitor ??= $this->createProjectorManager()->monitor();
    }

    public function setupEventStore(): void
    {
        if (! $this->eventStreamProvider) {
            $this->setupEventStreamProvider();
        }

        $this->chronicler ??= new InMemoryEventStore($this->eventStreamProvider);
    }

    public function setupClock(): void
    {
        $this->clock ??= new Clock();
    }

    public function setupSerializer(): void
    {
        if ($this->serializer) {
            return;
        }

        $factory = new ProjectionSerializerFactory(
            new JsonSerializerFactory()
        );

        $this->serializer = $factory->make();
    }

    public function setupDispatcher(): void
    {
        $this->dispatcher ??= new IlluminateEventDispatcher();
    }

    public function setupProjectionOption(): void
    {
        $this->projectionOption ??= new InMemoryOption();
    }

    public function setupEventStreamProvider(): void
    {
        $this->eventStreamProvider ??= new InMemoryEventStreamProvider();
    }

    public function setupProjectionProvider(): void
    {
        if (! $this->clock) {
            $this->setupClock();
        }

        $this->projectionProvider ??= new InMemoryProjectionProvider($this->clock);
    }

    public function setupQueryScope(): void
    {
        $this->queryScope ??= new InMemoryQueryScope();
    }
}
