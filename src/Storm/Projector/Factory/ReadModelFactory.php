<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\Option;
use Storm\Projector\Projection\Manager;
use Storm\Projector\Projection\ProcessManager;
use Storm\Projector\Projection\ReadingModelProjection;

final readonly class ReadModelFactory extends AbstractFactory
{
    public function create(?string $streamName, ?ReadModel $readModel, Option $options): Manager
    {
        $process = $this->createProcess($options);

        $repository = $this->createRepository($streamName, $options);

        $provider = new ReadingModelProjection($process, $repository, $readModel);
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
