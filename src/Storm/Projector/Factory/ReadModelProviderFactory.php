<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\Option;
use Storm\Projector\Provider\Manager;
use Storm\Projector\Provider\ProcessManager;
use Storm\Projector\Provider\ReadingModelProvider;

final readonly class ReadModelProviderFactory extends AbstractProviderFactory
{
    public function create(?string $streamName, ?ReadModel $readModel, Option $options): Manager
    {
        $process = $this->createProcessManager($options);

        $repository = $this->createRepository($streamName, $options);

        $provider = new ReadingModelProvider($process, $repository, $readModel);
        $this->subscribe($provider, $process);

        $activities = new ReadModelActivityFactory(
            $this->connection->eventStore(),
            $options,
            $this->connection->clock(),
            $readModel
        );

        return new ProcessManager($process, $activities);
    }
}
