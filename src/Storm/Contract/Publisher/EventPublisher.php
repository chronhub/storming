<?php

declare(strict_types=1);

namespace Storm\Contract\Publisher;

interface EventPublisher
{
    /**
     * Publish a collection of recorded domain events.
     */
    public function publish(int $batchSize = 100): void;
}
