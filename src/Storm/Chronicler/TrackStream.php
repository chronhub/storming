<?php

declare(strict_types=1);

namespace Storm\Chronicler;

use Storm\Contract\Tracker\StreamStory;
use Storm\Contract\Tracker\StreamTracker;
use Storm\Tracker\InteractWithTracker;

final class TrackStream implements StreamTracker
{
    use InteractWithTracker;

    public function newStory(string $eventName): StreamStory
    {
        return new StreamDraft($eventName);
    }
}
