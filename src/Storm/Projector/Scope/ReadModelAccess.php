<?php

declare(strict_types=1);

namespace Storm\Projector\Scope;

use ArrayAccess;
use Storm\Contract\Clock\SystemClock;
use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelScope;
use Storm\Projector\Support\Notification\Management\ProjectionClosed;
use Storm\Projector\Support\Notification\Stream\CurrentProcessedStream;

final class ReadModelAccess implements ArrayAccess, ReadModelScope
{
    use ScopeBehaviour;

    public function __construct(
        private readonly NotificationHub $hook,
        private readonly ReadModel $readModel,
        private readonly SystemClock $clock
    ) {
    }

    public function stop(): void
    {
        $this->hook->trigger(new ProjectionClosed());
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    public function stack(string $operation, ...$arguments): self
    {
        $this->readModel()->stack($operation, ...$arguments);

        return $this;
    }

    public function streamName(): string
    {
        return $this->hook->expect(CurrentProcessedStream::class);
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
