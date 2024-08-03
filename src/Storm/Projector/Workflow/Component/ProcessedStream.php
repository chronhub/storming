<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

class ProcessedStream
{
    /**
     * The current processed stream.
     */
    protected ?string $streamName = null;

    public function set(string $streamName): void
    {
        $this->streamName = $streamName;
    }

    public function get(): string
    {
        return $this->streamName;
    }
}
