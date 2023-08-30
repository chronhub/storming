<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

interface TransactionalStreamTracker extends StreamTracker
{
    public function newStory(string $eventName): TransactionalStreamStory;
}
