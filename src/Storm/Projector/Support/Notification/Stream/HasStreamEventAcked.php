<?php

declare(strict_types=1);

namespace Storm\Projector\Support\Notification\Stream;

use Storm\Contract\Projector\Subscriptor;

final class HasStreamEventAcked
{
    public function __invoke(Subscriptor $subscriptor): bool
    {
        return $subscriptor->watcher()->ackedStream()->hasStreams();
    }
}
