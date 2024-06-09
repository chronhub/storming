<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Closure;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\CheckpointRecognition;
use Storm\Contract\Projector\ContextReader;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Exception\RuntimeException;
use Storm\Projector\Iterator\MergeStreamIterator;
use Storm\Projector\ProjectionStatus;
use Storm\Projector\Workflow\Watcher\WatcherManager;

use function is_callable;

final class SubscriptionManager implements Subscriptor
{
    private ?string $streamName = null;

    private ?ContextReader $context = null;

    private ?MergeStreamIterator $streamIterator = null;

    private ProjectionStatus $status = ProjectionStatus::IDLE;

    public function __construct(
        private readonly CheckpointRecognition $checkpointRecognition,
        private readonly SystemClock $clock,
        private readonly ProjectionOption $option,
        private readonly WatcherManager $watcher,
    ) {
    }

    public function setContext(ContextReader $context, bool $allowRerun): void
    {
        if ($this->context !== null && ! $allowRerun) {
            throw new RuntimeException('Rerunning projection is not allowed');
        }

        $this->context = $context;
    }

    public function getContext(): ?ContextReader
    {
        return $this->context;
    }

    public function currentStatus(): ProjectionStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectionStatus $status): void
    {
        $this->status = $status;
    }

    public function restoreUserState(): void
    {
        $originalUserState = value($this->context->userState()) ?? [];

        $this->watcher->userState->put($originalUserState);
    }

    public function isUserStateInitialized(): bool
    {
        return $this->context->userState() instanceof Closure;
    }

    public function setProcessedStream(string $streamName): void
    {
        $this->streamName = $streamName;
    }

    public function getProcessedStream(): string
    {
        return $this->streamName;
    }

    public function setStreamIterator(?MergeStreamIterator $streamIterator): void
    {
        $this->streamIterator = $streamIterator;
    }

    public function pullStreamIterator(): ?MergeStreamIterator
    {
        $streamIterator = $this->streamIterator;

        $this->streamIterator = null;

        return $streamIterator;
    }

    public function discoverStreams(): void
    {
        tap($this->context->queries(), function (callable $query): void {
            $eventStreams = $this->watcher->streamDiscovery->discover($query);

            $this->checkpointRecognition->refreshStreams($eventStreams);
        });
    }

    public function recognition(): CheckpointRecognition
    {
        return $this->checkpointRecognition;
    }

    public function watcher(): WatcherManager
    {
        return $this->watcher;
    }

    public function option(): ProjectionOption
    {
        return $this->option;
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }

    public function capture(callable|object $event): mixed
    {
        return is_callable($event) ? $event($this) : $event;
    }
}
