<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\Option;
use Storm\Projector\Provider\EmittingProvider;
use Storm\Projector\Provider\GenericSubscription;
use Storm\Projector\Provider\Subscriptor;
use Storm\Projector\Workflow\EmittedStream;
use Storm\Projector\Workflow\InMemoryEmittedStreams;

final readonly class EmitterProviderFactory extends AbstractProviderFactory
{
    public function create(?string $streamName, ?ReadModel $readModel, Option $options): Subscriptor
    {
        $process = $this->createProcessManager($options);

        $provider = new EmittingProvider(
            $process,
            $this->createRepository($streamName, $options),
            $this->manager->eventStore(),
            new InMemoryEmittedStreams($options->getCacheSize()),
            new EmittedStream(),
            $options->getSleepEmitterOnFirstCommit()
        );

        $this->subscribe($provider, $process);

        $activities = new EmitterActivityFactory(
            $this->manager->eventStore(),
            $options,
            $this->manager->clock()
        );

        return new GenericSubscription($process, $activities);
    }
}
