<?php

declare(strict_types=1);

namespace Storm\Contract\Tracker;

interface MessageSubscriber extends Subscriber
{
    public function attachTo(MessageTracker $tracker): void;

    public function detachFrom(MessageTracker $tracker): void;
}
