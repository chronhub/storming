<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\ProjectionOption;
use Storm\Projector\Subscription\GenericSubscription;
use Storm\Projector\Subscription\QueryingManagement;
use Storm\Projector\Subscription\Subscriptor;

final readonly class QuerySubscriptionFactory extends AbstractSubscriptionFactory
{
    public function create(?string $streamName, ?ReadModel $readModel, ProjectionOption $options): Subscriptor
    {
        $process = $this->createProcessManager($options);

        $activities = new QueryActivityFactory(
            $this->manager->eventStore(),
            $options,
            $this->manager->clock()
        );

        $management = new QueryingManagement($process);
        $this->subscribe($management, $process);

        return new GenericSubscription($process, $activities);
    }
}
