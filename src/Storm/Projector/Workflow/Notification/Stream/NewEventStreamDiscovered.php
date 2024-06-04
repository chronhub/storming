<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Stream;

final readonly class NewEventStreamDiscovered
{
    public function __construct(public string $eventStream)
    {
    }
}
