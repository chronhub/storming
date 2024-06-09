<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Sprint;

use Storm\Contract\Projector\Subscriptor;

final readonly class SprintContinue
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->sprint->continue();
    }
}
