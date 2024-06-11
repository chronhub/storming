<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Watcher;

class AckedStreamWatcher
{
    protected array $streams = [];

    public function ack(string $streamName): void
    {
        if (! isset($this->streams[$streamName])) {
            $this->streams[] = $streamName;
        }
    }

    public function reset(): void
    {
        $this->streams = [];
    }

    public function hasStreams(): bool
    {
        return $this->streams !== [];
    }

    public function streams(): array
    {
        return $this->streams;
    }
}
