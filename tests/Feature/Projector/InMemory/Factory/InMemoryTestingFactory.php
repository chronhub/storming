<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Factory;

use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\InMemoryQueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\Monitoring;
use Storm\Contract\Projector\MonitoringManager;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\ProjectorServiceManager;
use Storm\Projector\Stream\Filter\InMemoryFromToPosition;

class InMemoryTestingFactory
{
    public InMemoryQueryFilter $inMemoryQueryFilter;

    protected ConnectionManager $connectionManager;

    public ?ProjectorManagerInterface $projectorManager = null;

    public function createProjectorManager(string $connection = 'in_memory'): ProjectorManagerInterface
    {
        if ($this->projectorManager) {
            return $this->projectorManager;
        }

        $this->setupQueryFilter();

        $this->connectionManager = app(ProjectorServiceManager::class)->connection($connection);

        return $this->projectorManager = app(ProjectorManagerInterface::class);
    }

    public function setupQueryFilter(): void
    {
        $this->inMemoryQueryFilter ??= new InMemoryFromToPosition();
    }

    public function getMonitor(): Monitoring
    {
        $connection = $this->connectionManager->connectionName();

        return app(MonitoringManager::class)->monitor($connection);
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
