<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\Repository;
use Storm\Projector\Checkpoint\CheckpointRecognition as Recognition;
use Storm\Projector\Checkpoint\Checkpoints;
use Storm\Projector\Checkpoint\GapDetector;
use Storm\Projector\Checkpoint\GapRecorder;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Factory\Component\CheckpointReckoning;
use Storm\Projector\Factory\Component\ComponentFactory;
use Storm\Projector\Factory\Component\Components;
use Storm\Projector\Factory\Component\Computation;
use Storm\Projector\Factory\Component\Contextualize;
use Storm\Projector\Factory\Component\EventStreamBatch;
use Storm\Projector\Factory\Component\EventStreamDiscovery;
use Storm\Projector\Factory\Component\HaltOn;
use Storm\Projector\Factory\Component\InMemoryCheckpoint;
use Storm\Projector\Factory\Component\Metrics;
use Storm\Projector\Factory\Component\ProcessedStream;
use Storm\Projector\Factory\Component\Sprint;
use Storm\Projector\Factory\Component\StatusHolder;
use Storm\Projector\Factory\Component\Timer;
use Storm\Projector\Factory\Component\UserState;
use Storm\Projector\Options\Option;
use Storm\Projector\Projection\Projection;
use Storm\Projector\Projection\ProviderEventMap;
use Storm\Projector\Storage\EventRepository;
use Storm\Projector\Storage\LockManager;
use Storm\Projector\Storage\ProjectionRepository;
use Storm\Projector\Support\ExponentialSleep;
use Storm\Projector\Workflow\Notify;
use Storm\Projector\Workflow\Process;

abstract readonly class AbstractFactory implements Factory
{
    public function __construct(
        protected ConnectionManager $connection
    ) {}

    protected function createRepository(string $streamName, Option $options): Repository
    {
        $repository = new ProjectionRepository(
            $this->connection->projectionProvider(),
            $this->createLockManager($options),
            $this->connection->serializer(),
            $streamName
        );

        if ($this->connection->dispatcher() !== null) {
            return new EventRepository($repository, $this->connection->dispatcher());
        }

        return $repository;
    }

    protected function createProcess(Option $options): Process
    {
        $factory = new ComponentFactory;
        $factory
            ->add('batch', $this->batchStreamEvent($options))
            ->add('compute', new Computation)
            ->add('context', new Contextualize)
            ->add('discovery', new EventStreamDiscovery($this->connection->eventStreamProvider()))
            ->add('dispatcher', new Notify)
            ->add('metrics', new Metrics($options->getBlockSize()))
            ->add('option', $options)
            ->add('recognition', $this->checkpointRecognition($options))
            ->add('stream', new ProcessedStream)
            ->add('sprint', new Sprint)
            ->add('status', new StatusHolder)
            ->add('stop', new HaltOn)
            ->add('time', new Timer($this->connection->clock()))
            ->add('userState', new UserState);

        $component = new Components($factory->toArray());

        return new Process($component);
    }

    protected function subscribe(Projection $management, Process $process): void
    {
        $map = new ProviderEventMap;

        $map->subscribeTo($management, $process);
    }

    protected function createLockManager(Option $options): LockManager
    {
        return new LockManager(
            $this->connection->clock(),
            $options->getTimeout(),
            $options->getLockout()
        );
    }

    protected function batchStreamEvent(Option $option): EventStreamBatch
    {
        $heapSleep = new ExponentialSleep(...$option->getSleep());

        return new EventStreamBatch($heapSleep);
    }

    protected function checkpointRecognition(Option $option): Recognition
    {
        $retries = $option->getRetries();
        $checkpoints = new Checkpoints($option->getRecordGap());
        $clock = $this->connection->clock();

        if ($retries === []) {
            return new InMemoryCheckpoint($checkpoints, $clock);
        }

        return new CheckpointReckoning(
            $checkpoints,
            $clock,
            new GapDetector($retries),
            new GapRecorder,
        );
    }
}
