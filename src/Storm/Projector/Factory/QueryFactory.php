<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\Option;
use Storm\Projector\Projection\Manager;
use Storm\Projector\Projection\ProcessManager;
use Storm\Projector\Projection\QueryingProjection;

final readonly class QueryFactory extends AbstractFactory
{
    public function create(?string $streamName, ?ReadModel $readModel, Option $options): Manager
    {
        $process = $this->createProcess($options);

        $activities = new QueryActivityFactory(
            $this->connection->eventStore(),
            $options,
            $this->connection->clock()
        );

        $provider = new QueryingProjection($process);
        $this->subscribe($provider, $process);

        return new ProcessManager($process, $activities);
    }
}
