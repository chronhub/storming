<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\Option;
use Storm\Projector\Provider\GenericSubscription;
use Storm\Projector\Provider\ReadingModelProvider;
use Storm\Projector\Provider\Subscriptor;

final readonly class ReadModelProviderFactory extends AbstractProviderFactory
{
    public function create(?string $streamName, ?ReadModel $readModel, Option $options): Subscriptor
    {
        $process = $this->createProcessManager($options);

        $projectionRepository = $this->createRepository($streamName, $options);

        $provider = new ReadingModelProvider($process, $projectionRepository, $readModel);
        $this->subscribe($provider, $process);

        $activities = new ReadModelActivityFactory(
            $this->manager->eventStore(),
            $options,
            $this->manager->clock(),
            $readModel
        );

        return new GenericSubscription($process, $activities);
    }
}
