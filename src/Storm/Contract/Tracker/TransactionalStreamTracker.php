<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

interface TransactionalStreamTracker extends StreamTracker
{
    /**
     * Create a new transactional stream story.
     */
    public function newStory(string $eventName): TransactionalStreamStory;
}
