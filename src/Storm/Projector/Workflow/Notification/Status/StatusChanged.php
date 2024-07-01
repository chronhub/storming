<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification\Status;

use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\ProjectionStatus;

final readonly class StatusChanged
{
    public function __construct(
        public ProjectionStatus $newStatus,
        public ProjectionStatus $oldStatus,
    ) {}

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->setStatus($this->newStatus);
    }
}
