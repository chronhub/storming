<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface QuerySubscriber extends Subscriber
{
    /**
     * Resets the stream positions nad user state.
     */
    public function resets(): void;
}
