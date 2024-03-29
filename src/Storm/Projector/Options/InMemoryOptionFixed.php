<?php

declare(strict_types=1);

namespace Storm\Projector\Options;

use Storm\Contract\Projector\ProjectionOptionImmutable;

final class InMemoryOptionFixed implements ProjectionOptionImmutable
{
    use ProvideOption;

    public function __construct()
    {
        $this->signal = false;
        $this->cacheSize = 100;
        $this->blockSize = 1;
        $this->sleep = [1, 10]; // fixed sleep time with a capacity of 1 every 0.1 second
        $this->timeout = 1;
        $this->lockout = 0;
        $this->loadLimiter = 100;
        $this->retries = [1];
        $this->detectionWindows = null;
        $this->onlyOnceDiscovery = false;
        $this->snapshotInterval = ['position' => 1000, 'time' => null, 'usleep' => null];
    }
}
