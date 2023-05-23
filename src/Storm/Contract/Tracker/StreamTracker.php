<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

interface StreamTracker extends Tracker
{
    public function newStory(string $eventName): StreamStory;
}
