<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\ProjectionOption;
use Storm\Projector\Subscription\Subscriptor;

interface SubscriptionFactory
{
    /**
     * Create a new subscription.
     */
    public function create(?string $streamName, ?ReadModel $readModel, ProjectionOption $options): Subscriptor;
}
