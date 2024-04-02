<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Stream;

use Storm\Contract\Projector\Subscriptor;

final class StreamEventAckedReset
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->ackedStream()->reset();
    }
}
