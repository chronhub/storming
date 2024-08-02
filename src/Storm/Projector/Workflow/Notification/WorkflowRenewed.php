<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification;

use Storm\Contract\Projector\EmitOnce;

final readonly class WorkflowRenewed implements EmitOnce
{
    public function __construct(public bool $isSprintTerminated) {}
}
