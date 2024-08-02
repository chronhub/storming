<?php

declare(strict_types=1);

namespace Storm\Projector\Factory;

use Override;
use Storm\Contract\Projector\ProjectionOption;
use Storm\Contract\Projector\Repository;
use Storm\Projector\Repository\GenericRepository;

final class InMemorySubscriptionFactory extends AbstractSubscriptionFactory
{
    private bool $useEvents = false;

    #[Override]
    public function useEvents(bool $useEvents): void
    {
        $this->useEvents = $useEvents;
    }

    #[Override]
    protected function createRepository(string $streamName, ProjectionOption $options): Repository
    {
        $repository = new GenericRepository(
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
