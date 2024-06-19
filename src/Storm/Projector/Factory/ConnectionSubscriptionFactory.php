<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Override;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\ProjectionRepository;
use Storm\Projector\Repository\ProjectionStore;

final class ConnectionSubscriptionFactory extends AbstractSubscriptionFactory
{
    private bool $useEvents = false;

    #[Override]
    public function useEvents(bool $useEvents): void
    {
        $this->useEvents = $useEvents;
    }

    #[Override]
    protected function createProjectionRepository(string $streamName, ProjectionOption $options): ProjectionRepository
    {
        $repository = new ProjectionStore(
            $this->projectionProvider,
            $this->createLockManager($options),
            $this->serializer,
            $streamName
        );

        if (! $this->useEvents) {
            return $repository;
        }

        return $this->createDispatcherRepository($repository);
    }
}
