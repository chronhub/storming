<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;

class DiscoverRemoteStatus
{
    use MonitorRemoteStatus;

    public readonly bool $onRise;

    public function __construct()
    {
        $this->onRise = true;
    }

    /**
     * Stop projection early if remote status is stopping or deleting
     */
    public function onlyOnce(NotificationHub $hub): bool
    {
        return $this->disclosedRemoteStatus($hub);
    }
}
