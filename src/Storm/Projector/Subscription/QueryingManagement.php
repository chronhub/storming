<?php

declare(strict_types=1);

namespace Storm\Projector\Subscription;

use Storm\Contract\Projector\NotificationHub;
use Storm\Contract\Projector\QueryManagement;

final readonly class QueryingManagement implements QueryManagement
{
    public function __construct(private NotificationHub $hub) {}

    public function hub(): NotificationHub
    {
        return $this->hub;
    }
}
