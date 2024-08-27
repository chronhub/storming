<?php

declare(strict_types=1);

namespace Storm\Chronicler\Publisher;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Storm\Contract\Message\DomainEvent;
use Storm\Story\StoryPublisher;

/**
 * Default Message Job for Story Publisher.
 */
class OutboxQueue implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DomainEvent $streamEvent,
        ?string $connection = null,
        ?string $queue = null,
    ) {
        $this->connection ??= $connection;
        $this->queue ??= $queue;
    }

    public function handle(StoryPublisher $storyPublisher): void
    {
        $storyPublisher->relay($this->streamEvent);
    }
}
