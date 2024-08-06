<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\Option;
use Storm\Projector\Provider\GenericSubscription;
use Storm\Projector\Provider\QueryingProvider;
use Storm\Projector\Provider\Subscriptor;

final readonly class QueryProviderFactory extends AbstractProviderFactory
{
    public function create(?string $streamName, ?ReadModel $readModel, Option $options): Subscriptor
    {
        $process = $this->createProcessManager($options);

        $activities = new QueryActivityFactory(
            $this->manager->eventStore(),
            $options,
            $this->manager->clock()
        );

        $provider = new QueryingProvider($process);
        $this->subscribe($provider, $process);

        return new GenericSubscription($process, $activities);
    }
}
