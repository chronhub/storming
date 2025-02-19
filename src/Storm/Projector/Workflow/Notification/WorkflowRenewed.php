<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification;

use Storm\Projector\Workflow\NotifyOnce;

final readonly class WorkflowRenewed implements NotifyOnce
{
    public function __construct(public bool $isSprintTerminated) {}
}
