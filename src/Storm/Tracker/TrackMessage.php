<?php

declare(strict_types=1);

namespace Storm\Tracker;

use Storm\Contract\Reporter\Reporter;
use Storm\Contract\Tracker\Listener;
use Storm\Contract\Tracker\MessageStory;
use Storm\Contract\Tracker\MessageTracker;

final class TrackMessage implements MessageTracker
{
    use InteractWithTracker;

    public function onDispatch(callable $story, int $priority = self::DEFAULT_PRIORITY): Listener
    {
        return $this->listen(
            $this->newListener(Reporter::DISPATCH_EVENT, $story, $priority)
        );
    }

    public function onFinalize(callable $story, int $priority = self::DEFAULT_PRIORITY): Listener
    {
        return $this->listen(
            $this->newListener(Reporter::FINALIZE_EVENT, $story, $priority)
        );
    }

    public function newStory(string $eventName): MessageStory
    {
        return new Draft($eventName);
    }
}
