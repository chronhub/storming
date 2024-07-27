<?php

declare(strict_types=1);

namespace Storm\Contract\Projector;

interface Management
{
    /**
     * Get the notification hub.
     */
    public function hub(): NotificationHub;

    /**
     * Perform actions when the threshold is reached.
     *
     * Action as:
     *  - persistent subscription may persist the batch stream when the threshold is reached.
     *  - query subscription may sleep for a while when the threshold is reached.
     *
     * @see ProjectionOption::BLOCK_SIZE
     * @see ProjectionOption::SLEEP
     */
    public function performWhenThresholdIsReached(): void;
}
