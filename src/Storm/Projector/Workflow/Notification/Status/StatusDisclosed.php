<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Status;

use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\ProjectionStatus;

final readonly class StatusDisclosed
{
    public function __construct(
        public ProjectionStatus $oldStatus,
        public ProjectionStatus $newStatus
    ) {}

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->setStatus($this->newStatus);
    }
}
