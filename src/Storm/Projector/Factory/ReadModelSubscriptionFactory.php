<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Subscription\GenericSubscription;
use Storm\Projector\Subscription\ReadingModelManagement;

final readonly class ReadModelSubscriptionFactory
{
    public function __construct(private SubscriptionBuilder $builder) {}

    public function create(string $streamName, ReadModel $readModel, ProjectionOption $options): Subscriptor
    {
        $process = $this->builder->createProcessManager($options);

        $projectionRepository = $this->builder->createRepository($streamName, $options);

        $management = new ReadingModelManagement($process, $projectionRepository, $readModel);
        $this->builder->subscribeToMap($management, $process);

        $activities = new ReadModelActivityFactory($this->builder->chronicler, $options, $this->builder->clock, $readModel);

        return new GenericSubscription($process, $activities);
    }
}
