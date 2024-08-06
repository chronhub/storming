<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\Repository;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Options\Option;
use Storm\Projector\Provider\Provider;
use Storm\Projector\Provider\ProviderEventMap;
use Storm\Projector\Repository\EventRepository;
use Storm\Projector\Repository\GenericRepository;
use Storm\Projector\Repository\LockManager;
use Storm\Projector\Workflow\Component;
use Storm\Projector\Workflow\Process;

abstract readonly class AbstractProviderFactory implements ProviderFactory
{
    public function __construct(protected ConnectionManager $manager) {}

    protected function createRepository(string $streamName, Option $options): Repository
    {
        $repository = new GenericRepository(
            $this->manager->projectionProvider(),
            $this->createLockManager($options),
            $this->manager->serializer(),
            $streamName
        );

        if ($this->manager->dispatcher() !== null) {
            return new EventRepository($repository, $this->manager->dispatcher());
        }

        return $repository;
    }

    protected function createProcessManager(Option $options): Process
    {
        $component = new Component(
            $options,
            $this->manager->eventStreamProvider(),
            $this->manager->clock()
        );

        return new Process($component);
    }

    protected function subscribe(Provider $management, Process $process): void
    {
        $map = new ProviderEventMap();

        $map->subscribeTo($management, $process);
    }

    protected function createLockManager(Option $options): LockManager
    {
        return new LockManager(
            $this->manager->clock(),
            $options->getTimeout(),
            $options->getLockout()
        );
    }
}
