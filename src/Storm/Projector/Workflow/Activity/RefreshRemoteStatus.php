<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;

class RefreshRemoteStatus
{
    use MonitorRemoteStatus;

    public readonly bool $onRise;

    public function __construct()
    {
        $this->onRise = true;
    }

    /**
     * Disclose the remote status.
     */
    public function refresh(NotificationHub $hub): void
    {
        $this->disclosedRemoteStatus($hub);
    }
}
