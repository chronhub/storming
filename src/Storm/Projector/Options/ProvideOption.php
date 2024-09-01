<?php

declare(strict_types=1);

namespace Storm\Projector\Options;

use function explode;
use function is_array;
use function range;

trait ProvideOption
{
    protected readonly bool $signal;

    /** @var positive-int */
    protected readonly int $cacheSize;

    /** @var positive-int */
    protected readonly int $timeout;

    /** @var int<0, max> */
    protected readonly int $lockout;

    /** @var array<positive-int,positive-int|float, positive-int> */
    protected readonly array $sleep;

    /** @var positive-int */
    protected readonly int $blockSize;

    /** @var array<int<0, max>>|array */
    protected readonly array $retries;

    protected readonly bool $recordGap;

    /** @var int<0, max>|null */
    protected readonly ?int $loadLimiter;

    protected readonly ?string $detectionWindows;

    protected readonly bool $onlyOnceDiscovery;

    /** @var int<0, max> */
    protected readonly int $sleepEmitterOnFirstCommit;

    public function getSignal(): bool
    {
        return $this->signal;
    }

    public function getCacheSize(): int
    {
        return $this->cacheSize;
    }

    public function getBlockSize(): int
    {
        return $this->blockSize;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getSleep(): array
    {
        return $this->sleep;
    }

    public function getLockout(): int
    {
        return $this->lockout;
    }

    public function getRetries(): array
    {
        return $this->retries;
    }

    public function getRecordGap(): bool
    {
        return $this->recordGap;
    }

    public function getLoadLimiter(): ?int
    {
        return $this->loadLimiter;
    }

    public function getDetectionWindows(): ?string
    {
        return $this->detectionWindows;
    }

    public function getOnlyOnceDiscovery(): bool
    {
        return $this->onlyOnceDiscovery;
    }

    public function getSleepEmitterOnFirstCommit(): int
    {
        return $this->sleepEmitterOnFirstCommit;
    }

    public function jsonSerialize(): array
    {
        return [
            self::SIGNAL => $this->getSignal(),
            self::CACHE_SIZE => $this->getCacheSize(),
            self::BLOCK_SIZE => $this->getBlockSize(),
            self::TIMEOUT => $this->getTimeout(),
            self::SLEEP => $this->getSleep(),
            self::LOCKOUT => $this->getLockout(),
            self::RETRIES => $this->getRetries(),
            self::RECORD_GAP => $this->getRecordGap(),
            self::DETECTION_WINDOWS => $this->getDetectionWindows(),
            self::LOAD_LIMITER => $this->getLoadLimiter(),
            self::ONLY_ONCE_DISCOVERY => $this->getOnlyOnceDiscovery(),
            self::SLEEP_EMITTER_ON_FIRST_COMMIT => $this->getSleepEmitterOnFirstCommit(),
        ];
    }

    protected function setUpRetries(array|string $retries): void
    {
        if (is_array($retries)) {
            $this->retries = $retries;
        } else {
            [$start, $end, $step] = explode(',', $retries);

            $this->retries = range((int) $start, (int) $end, (int) $step);
        }
    }
}
