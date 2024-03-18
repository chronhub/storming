<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface Management
{
    /**
     * Stop the subscription.
     */
    public function close(): void;

    /**
     * Get the notification hub.
     */
    public function hub(): NotificationHub;
}
