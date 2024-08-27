<?php

declare(strict_types=1);

namespace Storm\Projector\Options;

use JsonSerializable;
use Storm\Projector\Stream\Filter\LoadLimiter;
use Storm\Projector\Support\ExponentialSleep;
use Storm\Projector\Support\StopWhen;

interface Option extends JsonSerializable
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
        self::CACHE_SIZE => 'Number of event stream to keep in the cache',
        self::LOAD_LIMITER => 'Limit the number of stream events to load, when set to zero, no limit is applied',
        self::TIMEOUT => 'Lock threshold in milliseconds',
        self::LOCKOUT => 'Lock timeout in milliseconds',
        self::SLEEP => 'Exponential sleep time between empty batch stream event, ["base", "factor", "max"]',
        self::BLOCK_SIZE => 'Number of events to keep in memory before persisting',
        self::RETRIES => 'Retries in milliseconds when a gap is detected, set to empty to disable gap detection',
        self::RECORD_GAP => 'Enable record gaps in checkpoints, requires retries to be enabled',
        self::DETECTION_WINDOWS => 'Detection windows to bypass gaps, mostly used when resetting projection',
        self::ONLY_ONCE_DISCOVERY => 'Enable new event stream discovery after a workflow renewal',
        self::SLEEP_EMITTER_ON_FIRST_COMMIT => 'Sleep emitter on first commit in milliseconds',
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
     * @return int<0, max>
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
     * @see EventStreamBatch
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
     * @return array|array<int<0, max>>
     */
    public function getRetries(): array;

    /**
     * Should Record gaps in checkpoints.
     *
     * When the "retries" option is empty, record gaps should be disabled
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
     * Discover new event streams after each workflow renewal.
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
