<?php

declare(strict_types=1);

namespace Storm\Chronicler;

use Storm\Contract\Tracker\TransactionalStreamStory;
use Storm\Contract\Tracker\TransactionalStreamTracker;
use Storm\Tracker\InteractWithTracker;

final class TrackTransactionalStream implements TransactionalStreamTracker
{
    use InteractWithTracker;

    public function newStory(string $eventName): TransactionalStreamStory
    {
        return new TransactionalStreamDraft($eventName);
    }
}
