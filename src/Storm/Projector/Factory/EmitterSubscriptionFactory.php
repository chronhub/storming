<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\ProjectionOption;
use Storm\Projector\Subscription\EmittingManagement;
use Storm\Projector\Subscription\GenericSubscription;
use Storm\Projector\Subscription\Subscriptor;
use Storm\Projector\Workflow\EmittedStream;
use Storm\Projector\Workflow\InMemoryEmittedStreams;

final readonly class EmitterSubscriptionFactory extends AbstractSubscriptionFactory
{
    public function create(?string $streamName, ?ReadModel $readModel, ProjectionOption $options): Subscriptor
    {
        $process = $this->createProcessManager($options);

        $management = new EmittingManagement(
            $process,
            $this->createRepository($streamName, $options),
            $this->manager->eventStore(),
            new InMemoryEmittedStreams($options->getCacheSize()),
            new EmittedStream(),
            $options->getSleepEmitterOnFirstCommit()
        );

        $this->subscribe($management, $process);

        $activities = new EmitterActivityFactory(
            $this->manager->eventStore(),
            $options,
            $this->manager->clock()
        );

        return new GenericSubscription($process, $activities);
    }
}
