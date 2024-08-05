<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\Subscriptor;
use Storm\Projector\Subscription\EmittingManagement;
use Storm\Projector\Subscription\GenericSubscription;
use Storm\Projector\Workflow\EmittedStream;
use Storm\Projector\Workflow\InMemoryEmittedStreams;

final readonly class EmitterSubscriptionFactory
{
    public function __construct(private SubscriptionBuilder $builder) {}

    public function create(string $streamName, ProjectionOption $options): Subscriptor
    {
        $process = $this->builder->createProcessManager($options);

        $management = new EmittingManagement(
            $process,
            $this->builder->chronicler,
            $this->builder->createRepository($streamName, $options),
            new InMemoryEmittedStreams($options->getCacheSize()),
            new EmittedStream(),
            $options->getSleepEmitterOnFirstCommit()
        );

        $this->builder->subscribeToMap($management, $process);

        $activities = new EmitterActivityFactory($this->builder->chronicler, $options, $this->builder->clock);

        return new GenericSubscription($process, $activities);
    }
}
