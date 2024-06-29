<?php

declare(strict_types=1);

namespace Storm\Tests\Feature;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\Dispatcher as IlluminateEventDispatcher;
use RuntimeException;
use Storm\Chronicler\InMemory\InMemoryEventStore;
use Storm\Chronicler\InMemory\InMemoryEventStream;
use Storm\Clock\PointInTime;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\InMemoryChronicler;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectionQueryScope;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\SubscriptionFactory;
use Storm\Projector\Factory\InMemorySubscriptionFactory;
use Storm\Projector\Filter\InMemoryQueryScope;
use Storm\Projector\Options\InMemoryOption;
use Storm\Projector\ProjectorManager;
use Storm\Projector\Repository\InMemoryProjectionProvider;
use Storm\Serializer\JsonSerializerFactory;
use Storm\Stream\StreamCategoryDetector;
use Symfony\Component\Serializer\Serializer;

class InMemoryTestingFactory
{
    public ?InMemoryChronicler $chronicler = null;

    public ?EventStreamProvider $eventStreamProvider = null;

    public ?ProjectionProvider $projectionProvider = null;

    public ?SystemClock $clock = null;

    public ?Dispatcher $dispatcher = null;

    public ?Serializer $serializer = null;

    public ?ProjectionOption $projectionOption = null;

    public ?SubscriptionFactory $subscriptionFactory = null;

    public ?ProjectorManagerInterface $projectorManager = null;

    public ?ProjectionQueryScope $queryScope = null;

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

        return $this->projectorManager = new ProjectorManager(
            $this->createSubscriptionFactory()
        );
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
        $this->setupProjectionOption();
        $this->setupQueryScope();

        return $this->subscriptionFactory = new InMemorySubscriptionFactory(
            $this->chronicler,
            $this->projectionProvider,
            $this->eventStreamProvider,
            $this->clock,
            $this->serializer,
            $this->dispatcher,
            null,
            $this->projectionOption
        );
    }

    public function setupEventStore(): void
    {
        if (! $this->eventStreamProvider) {
            $this->setupEventStreamProvider();
        }

        $this->chronicler ??= new InMemoryEventStore(
            $this->eventStreamProvider,
            new StreamCategoryDetector()
        );
    }

    public function setupClock(): void
    {
        $this->clock ??= new PointInTime();
    }

    public function setupSerializer(): void
    {
        if ($this->serializer) {
            return;
        }

        $factory = new JsonSerializerFactory();
        $serializer = $factory->create();

        if (! $serializer instanceof Serializer) {
            throw new RuntimeException(
                'Testing serializer must be an instance of Symfony Serializer:'.Serializer::class
            );
        }

        $this->serializer = $serializer;
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
        $this->eventStreamProvider ??= new InMemoryEventStream();
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
