<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Handler;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Checkpoint\Checkpoint;
use Storm\Projector\Workflow\Notification\Promise\StreamEventProcessed;

/**
 * @deprecated
 */
final class WhenStreamEventProcessed
{
    public function __invoke(NotificationHub $hub, StreamEventProcessed $event, Checkpoint $checkpoint): void
    {
        if ($checkpoint->isGap()) {
            $listener = $checkpoint->gapType->value;

            $hub->emit($listener, $checkpoint);
        }
    }
}
