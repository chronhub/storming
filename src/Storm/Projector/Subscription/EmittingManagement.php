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
use Storm\Projector\Workflow\Notification\Sprint\SprintStopped;
use Storm\Projector\Workflow\Notification\Status\CurrentStatus;
use Storm\Projector\Workflow\Notification\Stream\EventStreamDiscovered;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

use function sleep;

final readonly class EmittingManagement implements EmitterManagement
{
    public const int DEFAULT_SLEEP_ON_FIRST_COMMIT = 2;

    use InteractWithManagement;

    public function __construct(
        protected NotificationHub $hub,
        protected Chronicler $chronicler,
        protected ProjectionRepository $projectionRepository,
        private EmittedStreamCache $streamCache,
        private EmittedStream $emittedStream,
    ) {
    }

    public function emit(DomainEvent $event): void
    {
        $streamName = new StreamName($this->getName());

        if ($this->streamNotEmittedAndNotExists($streamName)) {
            $this->appendStreamAndSleepOnFirstCommit(new Stream($streamName), true);

            $this->emittedStream->emitted();
        }

        // Append the stream with the event
        $this->linkTo($this->getName(), $event);
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

        $this->hub->notify(EventStreamDiscovered::class);

        $this->synchronise();
    }

    public function store(): void
    {
        $this->projectionRepository->persist($this->getProjectionResult());
    }

    public function revise(): void
    {
        $this->resetState();

        $this->projectionRepository->reset(
            $this->getProjectionResult(),
            $this->hub->expect(CurrentStatus::class)
        );

        $this->deleteStream();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->projectionRepository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->hub->notify(SprintStopped::class);

        $this->resetState();
    }

    /**
     * @todo
     * With standard strategy, we need to sleep,
     * to let the stream be created by the sql procedure
     */
    private function appendStreamAndSleepOnFirstCommit(Stream $stream, bool $shouldSleep): void
    {
        $this->chronicler->append($stream);

        if ($shouldSleep) {
            sleep(self::DEFAULT_SLEEP_ON_FIRST_COMMIT);
        }
    }

    private function streamNotEmittedAndNotExists(StreamName $streamName): bool
    {
        return ! $this->emittedStream->wasEmitted()
            && ! $this->chronicler->hasStream($streamName);
    }

    private function streamIsCachedOrExists(StreamName $streamName): bool
    {
        if ($this->streamCache->has($streamName->name)) {
            return true;
        }

        $this->streamCache->push($streamName->name);

        return $this->chronicler->hasStream($streamName);
    }

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
