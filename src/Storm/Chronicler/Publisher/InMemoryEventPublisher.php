<?php

declare(strict_types=1);

namespace Storm\Chronicler\Publisher;

use Storm\Contract\Message\DomainEvent;
use Storm\Contract\Publisher\EventPublisherRepository;
use Storm\Contract\Story\Publisher;

use function array_diff;
use function array_merge;
use function array_slice;
use function iterator_to_array;

final class InMemoryEventPublisher implements EventPublisherRepository
{
    /** @var array<DomainEvent>|array */
    private array $streamEvents = [];

    public function __construct(private readonly Publisher $publisher) {}

    public function publish(int $batchSize = 100): void
    {
        $streamEvents = $this->getUnprocessedEvents($batchSize);

        foreach ($streamEvents as $streamEvent) {
            $this->publisher->relay($streamEvent);
        }

        $this->markAsProcessed($streamEvents);
    }

    public function record(iterable $streamEvents): void
    {
        $this->streamEvents = array_merge($this->streamEvents, iterator_to_array($streamEvents));
    }

    public function deletePendingEvents(): void
    {
        $this->streamEvents = [];
    }

    private function markAsProcessed(iterable $streamEvents): void
    {
        $this->streamEvents = array_diff($this->streamEvents, iterator_to_array($streamEvents));
    }

    private function getUnprocessedEvents(int $batchSize = 100): iterable
    {
        return array_slice($this->streamEvents, 0, $batchSize);
    }
}
