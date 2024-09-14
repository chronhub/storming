<?php

declare(strict_types=1);

namespace Storm\Projector\Options;

final class InMemoryFixedOption implements OptionImmutable
{
    use ProvideOption;

    public function __construct()
    {
        $this->signal = false;
        $this->cacheSize = 100;
        $this->blockSize = 1;
        $this->sleep = [100, 1, 1000];
        $this->timeout = 1;
        $this->lockout = 0;
        $this->loadLimiter = 100;
        $this->sleepEmitterOnFirstCommit = 0;
        $this->retries = [];
        $this->recordGap = false;
        $this->detectionWindows = null;
        $this->onlyOnceDiscovery = false;
    }
}
