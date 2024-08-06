<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\Monitoring;
use Storm\Contract\Projector\MonitoringManager;

final class MonitorManager implements MonitoringManager
{
    /** @var array<string, Monitoring> */
    private array $monitors = [];

    public function __construct(private readonly ProjectorServiceManager $manager) {}

    public function monitor(?string $connection): Monitoring
    {
        $manager = $this->manager->connection($connection);

        if (! $connection) {
            $connection = $this->manager->getDefaultDriver();
        }

        return $this->monitors[$connection] ??= new ProjectorMonitor(
            $manager->projectionProvider(),
            $manager->serializer()
        );
    }
}
