<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Factory\Activity\EmitterActivityFactory;
use Storm\Projector\Options\Option;
use Storm\Projector\Projection\EmittingProjection;
use Storm\Projector\Projection\Manager;
use Storm\Projector\Projection\ProcessManager;
use Storm\Projector\Stream\EmittedStream;
use Storm\Projector\Stream\InMemoryEmittedStreams;

final readonly class EmitterFactory extends AbstractFactory
{
    public function create(?string $streamName, ?ReadModel $readModel, Option $options): Manager
    {
        $process = $this->createProcess($options);

        $provider = new EmittingProjection(
            $process,
            $this->createRepository($streamName, $options),
            $this->connection->eventStore(),
            new InMemoryEmittedStreams($options->getCacheSize()),
            new EmittedStream,
            $options->getSleepEmitterOnFirstCommit()
        );

        $this->subscribe($provider, $process);

        $activities = new EmitterActivityFactory(
            $this->connection->eventStore(),
            $options,
            $this->connection->clock()
        );

        return new ProcessManager($process, $activities);
    }
}
