<?php

declare(strict_types=1);

namespace Storm\Tracker;

use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\EventListener;
use Storm\Contract\Tracker\MessageStory;
use Storm\Contract\Tracker\MessageTracker;

final class TrackMessage implements MessageTracker
{
    use InteractWithTracker;

    public function onDispatch(callable $story, int $priority = 1): EventListener
    {
        return $this->watch(Reporter::DISPATCH_EVENT, $story, $priority);
    }

    public function onFinalize(callable $story, int $priority = 1): EventListener
    {
        return $this->watch(Reporter::FINALIZE_EVENT, $story, $priority);
    }

    public function newStory(string $eventName): MessageStory
    {
        return new Draft($eventName);
    }
}
