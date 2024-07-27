<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Chronicler\Exceptions\StreamNotFound;
use Storm\Contract\Chronicler\Chronicler;
use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Projector\EmittedStreamCache;
use Storm\Contract\Projector\EmitterManagement;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Projector\Workflow\EmittedStream;
use Storm\Projector\Workflow\Notification\Command\EventStreamDiscovered;
use Storm\Projector\Workflow\Notification\Command\SprintStopped;
use Storm\Projector\Workflow\Notification\Promise\CurrentStatus;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;
use Throwable;

use function usleep;

final readonly class EmittingManagement implements EmitterManagement
{
    use InteractWithManagement;

    public function __construct(
        protected NotificationHub $hub,
        protected Chronicler $chronicler,
        protected ProjectionRepository $projectionRepository,
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

        $this->hub->emit(EventStreamDiscovered::class);

        $this->synchronise();
    }

    public function store(): void
    {
        $this->projectionRepository->persist($this->takeSnapshot());
    }

    public function revise(): void
    {
        $this->resetSnapshot();

        $this->projectionRepository->reset(
            $this->takeSnapshot(),
            $this->hub->await(CurrentStatus::class)
        );

        $this->deleteStream();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->projectionRepository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->hub->emit(SprintStopped::class);

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
     *
     * fixMe we assume the cache is in memory
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
     * Note that we hold the stream not found for two main reasons:
     *  1. Stream has already been deleted.
     *  2. Stream that has been emitted under another stream (e.g., linkTo)
     *     must be deleted manually.
     *
     * @throws Throwable
     */
    private function deleteStream(): void
    {
        try {
            $streamName = new StreamName($this->projectionRepository->projectionName());

            $this->chronicler->delete($streamName);
        } catch (StreamNotFound) {
            // ignore
        }

        $this->emittedStream->unlink();
    }
}
