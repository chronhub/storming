<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\Monitoring;
use Storm\Contract\Projector\MonitoringManager;
use Storm\Contract\Projector\ProjectorManagement;

final class MonitorManager implements MonitoringManager
{
    /** @var array<string, Monitoring> */
    private array $monitors = [];

    public function __construct(private readonly ProjectorManagement $projector) {}

    public function monitor(?string $connection): Monitoring
    {
        $manager = $this->projector->connection($connection);

        if (! $connection) {
            $connection = $this->projector->getDefaultDriver();
        }

        return $this->monitors[$connection] ??= new ProjectorMonitor(
            $manager->projectionProvider(),
            $manager->serializer()
        );
    }
}
