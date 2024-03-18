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
use Storm\Contract\Projector\SnapshotRepository;
use Storm\Projector\Support\Notification\Sprint\SprintStopped;
use Storm\Projector\Support\Notification\Status\CurrentStatus;
use Storm\Projector\Support\Notification\Stream\EventStreamDiscovered;
use Storm\Projector\Workflow\EmittedStream;
use Storm\Stream\Stream;
use Storm\Stream\StreamName;

final readonly class EmittingManagement implements EmitterManagement
{
    use InteractWithManagement;

    public function __construct(
        protected NotificationHub $hub,
        protected Chronicler $chronicler,
        protected ProjectionRepository $projectionRepository,
        protected SnapshotRepository $snapshotRepository,
        private EmittedStreamCache $streamCache,
        private EmittedStream $emittedStream,
    ) {
    }

    public function emit(DomainEvent $event): void
    {
        $streamName = new StreamName($this->getName());

        // fixMe appendOnly

        // First commit the stream name without the event
        if ($this->streamNotEmittedAndNotExists($streamName)) {
            $this->chronicler->append(new Stream($streamName));

            $this->emittedStream->emitted();
        }

        // Append the stream with the event
        $this->linkTo($this->getName(), $event);
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $newStreamName = new StreamName($streamName);

        $stream = new Stream($newStreamName, [$event]);

        $this->streamIsCachedOrExists($newStreamName);

        $this->chronicler->append($stream);

        //        $this->streamIsCachedOrExists($newStreamName)
        //            ? $this->chronicler->amend($stream)
        //            : $this->chronicler->firstCommit($stream);
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

        $this->projectionRepository->reset($this->getProjectionResult(), $this->hub->expect(CurrentStatus::class));

        $this->deleteStream();

        $this->snapshotRepository->deleteByProjectionName($this->getName());
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->projectionRepository->delete($withEmittedEvents);

        $this->snapshotRepository->deleteByProjectionName($this->getName());

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->hub->notify(SprintStopped::class);

        $this->resetState();
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
