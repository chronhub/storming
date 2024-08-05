<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Subscription\GenericSubscription;
use Storm\Projector\Subscription\QueryingManagement;

final readonly class QuerySubscriptionFactory
{
    public function __construct(private SubscriptionBuilder $builder) {}

    public function create(ProjectionOption $options): Subscriptor
    {
        $process = $this->builder->createProcessManager($options);
        $activities = new QueryActivityFactory($this->builder->chronicler, $options, $this->builder->clock);

        $management = new QueryingManagement($process);
        $this->builder->subscribeToMap($management, $process);

        return new GenericSubscription($process, $activities);
    }
}
