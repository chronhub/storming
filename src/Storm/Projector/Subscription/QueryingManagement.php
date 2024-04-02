<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\QueryManagement;
use Storm\Projector\Workflow\Notification\Sprint\SprintStopped;

final readonly class QueryingManagement implements QueryManagement
{
    public function __construct(private NotificationHub $hub)
    {
    }

    public function close(): void
    {
        $this->hub->notify(SprintStopped::class);
    }

    public function hub(): NotificationHub
    {
        return $this->hub;
    }
}
