<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

interface StreamTracker extends Tracker
{
    /**
     * Create a new stream story.
     */
    public function newStory(string $eventName): StreamStory;
}
