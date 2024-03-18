<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use JsonSerializable;

interface ProjectionOption extends JsonSerializable
{
    public const string SIGNAL = 'signal';

    public const string CACHE_SIZE = 'cacheSize';

    public const string TIMEOUT = 'timeout';

    public const string SLEEP = 'sleep';

    public const string LOCKOUT = 'lockout';

    public const string BLOCK_SIZE = 'blockSize';

    public const string RETRIES = 'retries';

    public const string DETECTION_WINDOWS = 'detectionWindows';

    public const string LOAD_LIMITER = 'loadLimiter';

    public const string ONLY_ONCE_DISCOVERY = 'onlyOnceDiscovery';

    public const string SNAPSHOT_INTERVAL = 'snapshotInterval';

    /**
     * Dispatch async signal
     */
    public function getSignal(): bool;

    /**
     * Get the number of streams to keep in cache
     * Available for emitter projection
     *
     * @return positive-int
     */
    public function getCacheSize(): int;

    /**
     * Get the threshold of events to keep in memory before persisting
     * Available for persistent projection
     *
     * @return positive-int
     */
    public function getBlockSize(): int;

    /**
     * Get lock timeout in milliseconds
     * Available for persistent projection
     *
     * @return positive-int
     */
    public function getTimeout(): int;

    /**
     * Get the lock threshold in milliseconds
     * Available for persistent projection
     *
     * @return int<0,max>
     */
    public function getLockout(): int;

    /**
     * Get sleep times
     *
     * @return array{int|float, int|float}
     *
     * @see BatchStreamWatcher
     * @see ConsumeWithSleepToken
     *
     * @example [1, 2] fixed sleep time of 0.5 second on each query
     * @example [5, 2.5] increment sleep times of a total of 6 seconds for five queries
     */
    public function getSleep(): array;

    /**
     * Get retries in milliseconds when a gap detected
     * Available for persistent projection
     *
     * By now, two retries are mandatory,
     * as the last retry could be considered as an UnrecoverableGap
     * when halt is set to stop projection on an unrecoverable gap.
     *
     * @see StopWatcher
     * @see HaltOn
     *
     * @return array<int<0,max>>
     */
    public function getRetries(): array;

    /**
     * Get detection windows
     *
     * @deprecated still need to set a replacement with checkpoints
     *
     * @return null|string as date interval duration
     */
    public function getDetectionWindows(): ?string;

    /**
     * Get loads limiter for the query filter
     *
     * Zero means no limit
     *
     * @return int<0,max>
     *
     * @see LoadLimiterProjectionQueryFilter
     */
    public function getLoadLimiter(): int;

    /**
     * Get "only once discovery"
     * Available for persistent projection
     */
    public function getOnlyOnceDiscovery(): bool;

    /**
     * Get a snapshot interval periodically.
     *
     * Usleep meant for testing purpose and usleep while taking snapshot per interval
     *
     * @return array{position: null|positive-int, time: null|positive-int, usleep: null|positive-int}
     */
    public function getSnapshotInterval(): array;
}
