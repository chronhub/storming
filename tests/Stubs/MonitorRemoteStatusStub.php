<?php

declare(strict_types=1);

namespace Storm\Tests\Stubs;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Activity\MonitorRemoteStatus;

final readonly class MonitorRemoteStatusStub
{
    use MonitorRemoteStatus;

    public function __construct(public bool $onRise) {}

    public function handle(NotificationHub $hub): ?bool
    {
        $result = $this->discloseRemoteStatus($hub);

        return $this->onRise ? $result : null;
    }
}
