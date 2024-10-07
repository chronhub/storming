<?php

declare(strict_types=1);

namespace Storm\Projector\Projection;

use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\Repository;
use Storm\Projector\Stream\EmittedStream;
use Storm\Projector\Stream\EmittedStreamCache;
use Storm\Projector\Workflow\Input\DiscoverEventStream;
use Storm\Projector\Workflow\Process;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;
use Throwable;

use function usleep;

final readonly class EmittingProjection implements EmitterProjection
{
    use InteractWithProvider;

    public function __construct(
        protected Process $process,
        protected Repository $repository,
        private Chronicler $chronicler,
        private EmittedStreamCache $streamCache,
        private EmittedStream $emittedStream,
        private int $sleepOnFirstCommit,
    ) {}

    public function emit(DomainEvent $event): void
    {
        $projectionName = $this->getName();
        $streamName = new StreamName($projectionName);

        if ($this->streamNotEmittedAndNotExists($streamName)) {
            $this->appendStreamAndSleepOnFirstCommit(new Stream($streamName), true);

            $this->emittedStream->emitted();
        }

        $this->linkTo($projectionName, $event);
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $newStreamName = new StreamName($streamName);
        $stream = new Stream($newStreamName, [$event]);

        $exists = $this->streamIsCachedOrExists($newStreamName);

        $this->appendStreamAndSleepOnFirstCommit($stream, ! $exists);
    }

    public function rise(): void
    {
        $this->mountProjection();

        $this->process->call(new DiscoverEventStream);

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->takeSnapshot());
    }

    public function revise(): void
    {
        $this->resetSnapshot();

        $this->repository->reset(
            $this->takeSnapshot(),
            $this->process->status()->get()
        );

        $this->deleteStream();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->process->sprint()->halt();

        $this->resetSnapshot();
    }

    /**
     * Append the stream and sleep on the first commit.
     */
    private function appendStreamAndSleepOnFirstCommit(Stream $stream, bool $shouldSleep): void
    {
        $this->chronicler->append($stream);

        if ($shouldSleep) {
            usleep($this->sleepOnFirstCommit);
        }
    }

    /**
     * Check if the stream was not emitted and not exists.
     */
    private function streamNotEmittedAndNotExists(StreamName $streamName): bool
    {
        return ! $this->emittedStream->wasEmitted()
            && ! $this->chronicler->hasStream($streamName);
    }

    /**
     * Check if the emitted stream is cached or exists.
     * We assume the cache is in memory
     */
    private function streamIsCachedOrExists(StreamName $streamName): bool
    {
        if ($this->streamCache->has($streamName->name)) {
            return true;
        }

        $this->streamCache->push($streamName->name);

        return $this->chronicler->hasStream($streamName);
    }

    /**
     * Delete the stream and unlink the emitted stream.
     *
     * Note that we hold the stream not found for:
     *  1. Stream has already been deleted.
     *  2. Stream that has emitted and deleted "without emitted event" must be deleted manually.
     *  2. Stream that has been emitted under another stream (e.g., linkTo)
     *     with or without emitted events must be deleted manually.
     *
     * @throws Throwable
     */
    private function deleteStream(): void
    {
        try {
            $streamName = new StreamName($this->repository->getName());

            $this->chronicler->delete($streamName);
        } catch (StreamNotFound) {
            // ignore
        }

        $this->emittedStream->unlink();
    }
}
