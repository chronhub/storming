<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Projector\Repository\InMemoryRepository;

final class InMemorySubscriptionFactory extends AbstractSubscriptionFactory
{
    private bool $useEvents = false;

    public function useEvents(bool $useEvents): void
    {
        $this->useEvents = $useEvents;
    }

    protected function createProjectionRepository(string $streamName, ProjectionOption $options): ProjectionRepository
    {
        $repository = new InMemoryRepository(
            $this->projectionProvider,
            $this->createLockManager($options),
            $this->jsonSerializer,
            $streamName
        );

        if (! $this->useEvents) {
            return $repository;
        }

        return $this->createDispatcherRepository($repository);
    }
}
