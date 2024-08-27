<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Factory;

use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\MonitoringManager;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectorManager;
use Storm\Contract\Projector\ProjectorMonitor;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Connector\ConnectorManager;

class InMemoryTestingFactory
{
    protected ConnectionManager $connectionManager;

    public ?ProjectorManager $projectorManager = null;

    public function createProjectorManager(?string $connection = null): ProjectorManager
    {
        if ($this->projectorManager) {
            return $this->projectorManager;
        }

        if ($connection === null) {
            $connection = 'in_memory';
        }

        $connectorResolver = app(ConnectorManager::class);
        $connectorResolver->setDefaultDriver($connection);
        $this->connectionManager = $connectorResolver->connection($connection);

        return $this->projectorManager = app(ProjectorManager::class);
    }

    public function getMonitor(string $connection = 'in_memory'): ProjectorMonitor
    {
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

    public function getQueryFilter(): QueryFilter
    {
        return $this->connectionManager->queryFilter();
    }
}
