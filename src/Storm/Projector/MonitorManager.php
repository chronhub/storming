<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\ConnectorResolver;
use Storm\Contract\Projector\Monitoring;
use Storm\Contract\Projector\MonitoringManager;

final class MonitorManager implements MonitoringManager
{
    /** @var array<string, Monitoring>|array */
    private array $monitors = [];

    public function __construct(private readonly ConnectorResolver $connectorResolver) {}

    public function monitor(?string $connection): Monitoring
    {
        $manager = $this->connectorResolver->connection($connection);

        if (! $connection) {
            $connection = $this->connectorResolver->getDefaultDriver();
        }

        return $this->monitors[$connection] ??= new ProjectorMonitor(
            $manager->projectionProvider(),
            $manager->serializer()
        );
    }
}
