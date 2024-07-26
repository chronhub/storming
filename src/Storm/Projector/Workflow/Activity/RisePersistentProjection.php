<?php

declare(strict_types=1);

namespace Storm\Projector\Workflow\Activity;

use Storm\Contract\Projector\NotificationHub;
use Storm\Projector\Workflow\Notification\IsFirstWorkflowCycle;
use Storm\Projector\Workflow\Notification\Management\ProjectionRise;

final readonly class RisePersistentProjection
{
    use MonitorRemoteStatus;

    protected bool $onRise;

    public function __construct()
    {
        $this->onRise = true;
    }

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        if ($hub->await(IsFirstWorkflowCycle::class)) {
            if ($this->discloseRemoteStatus($hub)) {
                return false;
            }

            $hub->emit(new ProjectionRise());
        }

        return $next($hub);
    }
}
