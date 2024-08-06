<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface MonitoringManager
{
    /**
     * Get a monitor for the given connection.
     */
    public function monitor(?string $connection): Monitoring;
}
