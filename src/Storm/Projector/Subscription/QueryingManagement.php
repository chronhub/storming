<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\QueryManagement;
use Storm\Projector\Workflow\Notification\Command\BatchStreamReset;
use Storm\Projector\Workflow\Notification\Promise\IsBatchStreamLimitReached;

final readonly class QueryingManagement implements QueryManagement
{
    public function __construct(private NotificationHub $hub) {}

    public function hub(): NotificationHub
    {
        return $this->hub;
    }

    public function performWhenThresholdIsReached(): void
    {
        if ($this->hub->await(IsBatchStreamLimitReached::class)) {
            $this->hub->emit(BatchStreamReset::class);
        }
    }
}
