<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface Management
{
    /**
     * Get the notification hub.
     */
    public function hub(): NotificationHub;
}
