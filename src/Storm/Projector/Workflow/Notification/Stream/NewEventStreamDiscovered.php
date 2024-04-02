<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Stream;

final class NewEventStreamDiscovered
{
    public function __construct(public readonly string $eventStream)
    {
    }
}
