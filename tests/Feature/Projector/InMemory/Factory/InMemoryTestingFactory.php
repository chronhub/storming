<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Factory;

use Storm\Contract\Chronicler\Chronicler;
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

    protected ConnectionManager $connectionManager;

    public ?ProjectorManagerInterface $projectorManager = null;

    public function createProjectorManager(): ProjectorManagerInterface
    {
        if ($this->projectorManager) {
            return $this->projectorManager;
        }

        $this->setupQueryFilter();

        $this->connectionManager = app(ProjectorServiceManager::class)->connection();

        return $this->projectorManager = app(ProjectorManagerInterface::class);
    }

    public function setupQueryFilter(): void
    {
        $this->inMemoryQueryFilter ??= new InMemoryFromToPosition();
    }

    public function getMonitor(): ProjectorMonitorInterface
    {
        $connection = $this->connectionManager->connectionName();

        return $this->projectorManager->monitor($connection);
    }

    public function getProjectionProvider(): ProjectionProvider
    {
        return $this->connectionManager->projectionProvider();
    }

    public function getEventStore(): Chronicler
    {
        return $this->connectionManager->eventStore();
    }

    public function getClock(): SystemClock
    {
        return $this->connectionManager->clock();
    }

    public function getSerializer(): SymfonySerializer
    {
        return $this->connectionManager->serializer();
    }
}
