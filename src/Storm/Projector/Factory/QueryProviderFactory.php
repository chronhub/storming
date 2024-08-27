<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\Option;
use Storm\Projector\Provider\Manager;
use Storm\Projector\Provider\QueryingProvider;
use Storm\Projector\Provider\WorkflowManager;

final readonly class QueryProviderFactory extends AbstractProviderFactory
{
    public function create(?string $streamName, ?ReadModel $readModel, Option $options): Manager
    {
        $process = $this->createProcessManager($options);

        $activities = new QueryActivityFactory(
            $this->connection->eventStore(),
            $options,
            $this->connection->clock()
        );

        $provider = new QueryingProvider($process);
        $this->subscribe($provider, $process);

        return new WorkflowManager($process, $activities);
    }
}
