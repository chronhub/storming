<?php

declare(strict_types=1);

namespace Storm\Projector\Options;

final class DefaultOption implements ProjectionOption
{
    use ProvideOption;

    public function __construct(
        protected readonly bool $signal = false,
        protected readonly int $cacheSize = 1000,
        protected readonly int $blockSize = 1000,
        protected readonly array $sleep = [10000, 5, 1000000],
        protected readonly int $timeout = 10000,
        protected readonly int $lockout = 1000000,
        protected readonly int $loadLimiter = 1000,
        protected readonly int $sleepEmitterOnFirstCommit = 1000,
        protected readonly bool $onlyOnceDiscovery = false,
        array|string $retries = [0, 5, 10, 25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500],
        protected readonly bool $recordGap = false,
        protected readonly ?string $detectionWindows = null
    ) {
        $this->setUpRetries($retries);
    }
}
