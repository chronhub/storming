<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ReadModel;
use Storm\Projector\Options\Option;
use Storm\Projector\Provider\EmittingProvider;
use Storm\Projector\Provider\Manager;
use Storm\Projector\Provider\ProcessManager;
use Storm\Projector\Stream\EmittedStream;
use Storm\Projector\Stream\InMemoryEmittedStreams;

final readonly class EmitterProviderFactory extends AbstractProviderFactory
{
    public function create(?string $streamName, ?ReadModel $readModel, Option $options): Manager
    {
        $process = $this->createProcessManager($options);

        $provider = new EmittingProvider(
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
