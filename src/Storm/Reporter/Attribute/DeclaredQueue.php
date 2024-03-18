<?php

declare(strict_types=1);

namespace Storm\Reporter\Attribute;

use Illuminate\Contracts\Container\Container;
use RuntimeException;

use function array_merge;
use function is_object;
use function is_string;
use function sprintf;

class DeclaredQueue
{
    public const ERROR_DEFAULT_QUEUE_NOT_DEFINED_FOR_ASYNC = 'Default queue cannot be null for reporter %s when mode is async';

    public const ERROR_QUEUE_DEFINED_FOR_ASYNC = 'Handler queue cannot be defined for reporter %s when mode is async';

    public const ERROR_DEFAULT_QUEUE_NOT_DEFINED = 'Default queue cannot be null for reporter %s when mode is delegate to merge';

    /**
     * @param array<ReporterQueue> $queues
     */
    public function __construct(
        protected array $queues,
        protected Container $container
    ) {
    }

    public function mergeIfNeeded(string $reporterId, null|string|array|object $queue): ?array
    {
        $reporterQueue = $this->queues[$reporterId] ?? null;

        if ($reporterQueue === null) {
            throw new RuntimeException(sprintf('Reporter queue configuration %s is not defined', $reporterId));
        }

        if (is_object($queue)) {
            $queue = $queue->jsonSerialize();
        }

        if (is_string($queue)) {
            $queue = $this->container[$queue]->jsonSerialize();
        }

        return $this->resolve($reporterQueue, $reporterId, $queue);
    }

    /**
     * @return array<ReporterQueue>
     */
    public function getQueues(): array
    {
        return $this->queues;
    }

    public function getQueueById(string $reporterId): ?ReporterQueue
    {
        return $this->queues[$reporterId] ?? null;
    }

    protected function resolve(ReporterQueue $declared, string $reporterId, ?array $handlerQueue): ?array
    {
        // force sync even for handler would have queue defined
        if ($declared->mode->isSync()) {
            return null;
        }

        // force async all handlers to use default queue
        if ($declared->mode->isAsync()) {
            if ($declared->default === null) {
                throw new RuntimeException(sprintf(self::ERROR_DEFAULT_QUEUE_NOT_DEFINED_FOR_ASYNC, $reporterId));
            }

            if ($handlerQueue !== null) {
                throw new RuntimeException(sprintf(self::ERROR_QUEUE_DEFINED_FOR_ASYNC, $reporterId));
            }

            return $this->mergeWithDefaultQueue($declared->default, null);
        }

        // delegate to handler queue but merge with only required default queue when queue exists
        if ($declared->mode->isDelegateMerge()) {
            return match (true) {
                $handlerQueue === null => null,
                $declared->default !== null => $this->mergeWithDefaultQueue($declared->default, $handlerQueue),
                default => throw new RuntimeException(sprintf(self::ERROR_DEFAULT_QUEUE_NOT_DEFINED, $reporterId))
            };
        }

        // delegate to handler queue
        return $handlerQueue;
    }

    protected function mergeWithDefaultQueue(string|array $defaultQueue, ?array $handlerQueue): array
    {
        if (is_string($defaultQueue)) {
            $defaultQueue = $this->container[$defaultQueue]->jsonSerialize();
        }

        return array_merge($defaultQueue, $handlerQueue ?? []);
    }
}
