<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\Repository;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Options\ProjectionOption;
use Storm\Projector\Repository\EventRepository;
use Storm\Projector\Repository\GenericRepository;
use Storm\Projector\Repository\LockManager;
use Storm\Projector\Subscription\Management;
use Storm\Projector\Subscription\ManagementEventMap;
use Storm\Projector\Workflow\Component;
use Storm\Projector\Workflow\Process;

abstract readonly class AbstractSubscriptionFactory implements SubscriptionFactory
{
    public function __construct(protected ConnectionManager $manager) {}

    protected function createRepository(string $streamName, ProjectionOption $options): Repository
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

    protected function createProcessManager(ProjectionOption $options): Process
    {
        $component = new Component(
            $options,
            $this->manager->eventStoreProvider(),
            $this->manager->clock()
        );

        return new Process($component);
    }

    protected function subscribe(Management $management, Process $process): void
    {
        $map = new ManagementEventMap();

        $map->subscribeTo($management, $process);
    }

    protected function createLockManager(ProjectionOption $options): LockManager
    {
        return new LockManager(
            $this->manager->clock(),
            $options->getTimeout(),
            $options->getLockout()
        );
    }
}
