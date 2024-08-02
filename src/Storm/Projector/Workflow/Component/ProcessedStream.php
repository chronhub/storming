<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

class ProcessedStream
{
    /**
     * The current processed stream.
     */
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
     */
    public function get(): string
    {
        return $this->streamName;
    }
}
