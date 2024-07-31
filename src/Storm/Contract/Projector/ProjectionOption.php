<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use JsonSerializable;
use Storm\Projector\Support\ExponentialSleep;
use Storm\Projector\Support\StopWhen;

interface ProjectionOption extends JsonSerializable
{
    public const string SIGNAL = 'signal';

    public const string CACHE_SIZE = 'cacheSize';

    public const string TIMEOUT = 'timeout';

    public const string SLEEP = 'sleep';

    public const string LOCKOUT = 'lockout';

    public const string BLOCK_SIZE = 'blockSize';

    public const string RETRIES = 'retries';

    public const string RECORD_GAP = 'recordGap';

    public const string DETECTION_WINDOWS = 'detectionWindows';

    public const string LOAD_LIMITER = 'loadLimiter';

    public const string ONLY_ONCE_DISCOVERY = 'onlyOnceDiscovery';

    public const string SLEEP_EMITTER_ON_FIRST_COMMIT = 'sleepEmitterOnFirstCommit';

    public const array DESCRIPTIONS = [
        self::SIGNAL => 'Enable async signal dispatch',
        self::CACHE_SIZE => 'Get the number of event streams to keep in the cache',
        self::LOAD_LIMITER => 'Get loads limiter for the query filter',
        self::TIMEOUT => 'Get the threshold of events to keep in memory before persisting',
        self::LOCKOUT => 'Get lock timeout in milliseconds',
        self::SLEEP => 'Get sleep time between empty batch stream events',
        self::BLOCK_SIZE => 'Get the lock threshold in milliseconds',
        self::RETRIES => 'Get retries in milliseconds when a gap detected',
        self::RECORD_GAP => 'Enable record gaps in checkpoints',
        self::DETECTION_WINDOWS => 'Get detection windows',
        self::ONLY_ONCE_DISCOVERY => 'Enable discovery of new event streams after each end of a projection cycle',
        self::SLEEP_EMITTER_ON_FIRST_COMMIT => 'Get sleep emitter on first commit in milliseconds',
    ];

    /**
     * Dispatch async signal
     */
    public function getSignal(): bool;

    /**
     * Get the number of streams to keep in the cache
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
     * Get sleep time.
     * Increment sleep time between empty stream events loaded.
     *
     * Base sleep time in millisecond
     * Factor is the multiplier for the base sleep time
     * Max is the maximum sleep time in microsecond
     *
     * @return array{
     *     base: positive-int,
     *     factor: positive-int|float,
     *     max: positive-int,
     * }
     *
     * @see StreamEventBatch
     * @see ExponentialSleep
     */
    public function getSleep(): array;

    /**
     * Get retries in milliseconds when a gap detected
     *
     * By now, two retries are mandatory when detect a gap
     * as the last retry could be considered as an UnrecoverableGap
     * when halt is set to stop projection on an unrecoverable gap.
     *
     * To disable gap detection, set an empty array
     *
     * @see HaltOn
     * @see StopWhen
     *
     * @return array<int<0, max>>
     */
    public function getRetries(): array;

    /**
     * Should Record gaps in checkpoints.
     *
     * When retries are empty, record gaps should be disabled
     *
     * fixMe: this feature is wip and needs his own storage
     *  by now, it's recorded in checkpoints
     */
    public function getRecordGap(): bool;

    /**
     * Get detection windows
     *
     * @deprecated still need to set a replacement with checkpoints
     *
     * @return null|string as date interval duration
     */
    public function getDetectionWindows(): ?string;

    /**
     * Get loads limiter for the query filter.
     *
     * Zero means no limit and will be converted to PHP_INT_MAX
     *
     * @return int<0, max>
     *
     * @see LoadLimiterProjectionQueryFilter
     * @see LoadLimiter
     */
    public function getLoadLimiter(): int;

    /**
     * Discover new event streams after each end of a projection cycle.
     * Available for persistent projection
     */
    public function getOnlyOnceDiscovery(): bool;

    /**
     * Get sleep emitter on first commit in milliseconds.
     * Available for emitter projection and depends on strategy.
     *
     * @return int<0, max>
     */
    public function getSleepEmitterOnFirstCommit(): int;
}
