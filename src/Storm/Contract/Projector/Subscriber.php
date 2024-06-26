<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

use Closure;

interface Subscriber
{
    /**
     * Start subscription
     */
    public function start(ContextReader $context, bool $keepRunning): void;

    /**
     * Interact with notification hub
     *
     * @param Closure(NotificationHub): mixed $callback
     */
    public function interact(Closure $callback): mixed;
}
