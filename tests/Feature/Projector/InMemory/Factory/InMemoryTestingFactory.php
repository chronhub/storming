<?php

declare(strict_types=1);

namespace Storm\Tests\Feature\Projector\InMemory\Factory;

use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Chronicler\QueryFilter;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\ConnectorResolver;
use Storm\Contract\Projector\Monitoring;
use Storm\Contract\Projector\MonitoringManager;
use Storm\Contract\Projector\ProjectionProvider;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Serializer\SymfonySerializer;
use Storm\Projector\Connector\ConnectionManager;

class InMemoryTestingFactory
{
    protected ConnectionManager $connectionManager;

    public ?ProjectorManagerInterface $projectorManager = null;

    public function createProjectorManager(?string $connection = null): ProjectorManagerInterface
    {
        if ($this->projectorManager) {
            return $this->projectorManager;
        }

        if ($connection === null) {
            $connection = 'in_memory';
        }

        $connectorResolver = app(ConnectorResolver::class);
        $connectorResolver->setDefaultDriver($connection);
        $this->connectionManager = $connectorResolver->connection($connection);

        return $this->projectorManager = app(ProjectorManagerInterface::class);
    }

    public function getMonitor(string $connection = 'in_memory'): Monitoring
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
