<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface ShouldAgentSubscribe
{
    /**
     * Subscribe to the notification hub.
     */
    public function subscribe(NotificationHub $hub, ContextReader $context): void;
}
