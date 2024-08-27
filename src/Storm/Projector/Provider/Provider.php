<?php

declare(strict_types=1);

namespace Storm\Projector\Provider;

interface Provider
{
    /**
     * Perform actions when the threshold is reached.
     *
     * Action as:
     *  - persistent subscription may persist a batch stream event when the threshold is reached.
     *  - query subscription may sleep for a while when the threshold is reached.
     *
     * @see Option::BLOCK_SIZE
     * @see Option::SLEEP
     */
    public function performWhenThresholdIsReached(): void;
}
