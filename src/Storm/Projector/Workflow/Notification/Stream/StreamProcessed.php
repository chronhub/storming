<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Stream;

use Storm\Contract\Projector\Subscriptor;

final readonly class StreamProcessed
{
    public function __construct(public string $streamName)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->setProcessedStream($this->streamName);
    }
}
