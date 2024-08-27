<?php

declare(strict_types=1);

namespace Storm\Projector\Stream;

use JsonSerializable;
use Storm\Projector\Exception\InvalidArgumentException;

/**
 * @property-read positive-int $cacheSize
 */
interface EmittedStreamCache extends JsonSerializable
{
    /**
     * Add or replace stream name at the current position in the circular buffer.
     *
     * @param non-empty-string $streamName
     *
     * @throws InvalidArgumentException When stream name is already in buffer.
     */
    public function push(string $streamName): void;

    /**
     * Check if the stream name is in buffer.
     *
     * @param non-empty-string $streamName
     */
    public function has(string $streamName): bool;
}
