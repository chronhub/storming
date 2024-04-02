<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Status;

use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\ProjectionStatus;

final class CurrentStatus
{
    public function __invoke(Subscriptor $subscriptor): ProjectionStatus
    {
        return $subscriptor->currentStatus();
    }
}
