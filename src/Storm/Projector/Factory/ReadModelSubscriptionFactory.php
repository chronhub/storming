<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\ProjectionOption;
use Storm\Projector\Subscription\GenericSubscription;
use Storm\Projector\Subscription\ReadingModelManagement;
use Storm\Projector\Subscription\Subscriptor;

final readonly class ReadModelSubscriptionFactory extends AbstractSubscriptionFactory
{
    public function create(?string $streamName, ?ReadModel $readModel, ProjectionOption $options): Subscriptor
    {
        $process = $this->createProcessManager($options);

        $projectionRepository = $this->createRepository($streamName, $options);

        $management = new ReadingModelManagement($process, $projectionRepository, $readModel);
        $this->subscribe($management, $process);

        $activities = new ReadModelActivityFactory(
            $this->manager->eventStore(),
            $options,
            $this->manager->clock(),
            $readModel
        );

        return new GenericSubscription($process, $activities);
    }
}
