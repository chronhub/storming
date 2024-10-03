<?php

declare(strict_types=1);

namespace Storm\Projector\Factory\Component;

use Storm\Projector\ProjectionStatus;

class StatusHolder
{
    protected ProjectionStatus $status = ProjectionStatus::IDLE;

    public function set(ProjectionStatus $status): void
    {
        $this->status = $status;
    }

    public function get(): ProjectionStatus
    {
        return $this->status;
    }
}
