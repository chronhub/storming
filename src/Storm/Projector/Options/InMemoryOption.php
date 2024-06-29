<?php

declare(strict_types=1);

namespace Storm\Projector\Options;

use Storm\Contract\Projector\ProjectionOption;

final class InMemoryOption implements ProjectionOption
{
    use ProvideOption;

    public function __construct(
        protected readonly bool $signal = false,
        protected readonly int $cacheSize = 100,
        protected readonly int $blockSize = 100,
        protected readonly array $sleep = [1, 10],
        protected readonly int $timeout = 1,
        protected readonly int $lockout = 0,
        protected readonly int $loadLimiter = 100,
        protected readonly int $sleepEmitterOnFirstCommit = 0,
        array|string $retries = [],
        protected readonly ?string $detectionWindows = null,
        protected readonly bool $onlyOnceDiscovery = false,
        protected readonly array $snapshotInterval = ['position' => 1000, 'time' => null, 'usleep' => null],
    ) {
        $this->setUpRetries($retries);
    }
}
