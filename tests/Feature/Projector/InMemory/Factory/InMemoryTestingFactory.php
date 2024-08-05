<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Factory;

use Options\ProjectionOption;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\EventStreamProvider;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\ProjectorMonitorInterface;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Filter\InMemoryFromToPosition;
use Storm\Projector\ProjectorServiceManager;

class InMemoryTestingFactory
{
    public InMemoryQueryFilter $inMemoryQueryFilter;

    protected ConnectionManager $subscriptionManager;

    public ?ProjectorManagerInterface $projectorManager = null;

    protected ?ProjectorMonitorInterface $monitor = null;

    public function createProjectorManager(): ProjectorManagerInterface
    {
        if ($this->projectorManager) {
            return $this->projectorManager;
        }

        $this->setupQueryFilter();

        $this->subscriptionManager = app(ProjectorServiceManager::class)->connection();

        return $this->projectorManager = app(ProjectorManagerInterface::class);
    }

    public function setupQueryFilter(): void
    {
        $this->inMemoryQueryFilter ??= new InMemoryFromToPosition();
    }

    public function getMonitor(): ProjectorMonitorInterface
    {
        return $this->monitor ??= $this->createProjectorManager()->monitor();
    }

    public function getProjectionProvider(): ProjectionProvider
    {
        return $this->subscriptionManager->projectionProvider();
    }

    public function getEventStore(): Chronicler
    {
        return $this->subscriptionManager->eventStore();
    }

    public function getEventStoreProvider(): EventStreamProvider
    {
        return $this->subscriptionManager->eventStoreProvider();
    }

    public function getProjectionOption(): ProjectionOption
    {
        return $this->subscriptionManager->getProjectionOption();
    }

    public function getClock(): SystemClock
    {
        return $this->subscriptionManager->clock();
    }

    public function getSerializer(): SymfonySerializer
    {
        return $this->subscriptionManager->serializer();
    }
}
