<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Notification;

use Storm\Contract\Projector\Subscriptor;

final class GetReport
{
    public function __invoke(Subscriptor $subscriptor): array
    {
        return $subscriptor->watcher()->report->getReport();
    }
}
