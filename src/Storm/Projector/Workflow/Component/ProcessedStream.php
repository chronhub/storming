<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

use Storm\Projector\Exception\RuntimeException;

class ProcessedStream
{
    protected ?string $streamName = null;

    /**
     * Set the processed stream name.
     */
    public function set(string $streamName): void
    {
        $this->streamName = $streamName;
    }

    /**
     * Get the processed stream name.
     *
     * @throws RuntimeException when the stream name is not set
     */
    public function get(): string
    {
        if (! $this->streamName) {
            throw new RuntimeException('Processed stream name is not set');
        }

        return $this->streamName;
    }
}
