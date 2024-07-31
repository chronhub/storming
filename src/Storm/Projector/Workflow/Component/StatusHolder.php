<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Component;

use Storm\Projector\ProjectionStatus;

final class StatusHolder
{
    private ProjectionStatus $status = ProjectionStatus::IDLE;

    public function set(ProjectionStatus $status): void
    {
        $this->status = $status;
    }

    public function get(): ProjectionStatus
    {
        return $this->status;
    }
}
