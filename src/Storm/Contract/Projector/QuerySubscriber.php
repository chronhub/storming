<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface QuerySubscriber extends Subscriber
{
    /**
     * Resets the stream positions and user state.
     */
    public function resets(): void;
}
