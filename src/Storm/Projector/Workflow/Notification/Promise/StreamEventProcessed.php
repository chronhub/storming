<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Promise;

use Storm\Clock\PointInTime;
use Storm\Contract\Projector\AgentManager;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Checkpoint\StreamPoint;
use Storm\Stream\StreamPosition;

final readonly class StreamEventProcessed
{
    public function __construct(
        public string $streamName,
        public StreamPosition $streamPosition,
        public string|PointInTime $eventTime,
    ) {}

    public function __invoke(AgentManager $agentRegistry): Checkpoint
    {
        $streamPoint = new StreamPoint(
            $this->streamName,
            $this->streamPosition,
            $this->eventTime
        );

        return $agentRegistry->recognition()->record($streamPoint);
    }
}
