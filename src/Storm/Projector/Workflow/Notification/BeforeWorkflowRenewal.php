<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification;

use Storm\Projector\Workflow\EmitOnce;

final readonly class BeforeWorkflowRenewal implements EmitOnce
{
    public function __construct(public bool $isSprintTerminated) {}
}
