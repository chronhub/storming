<?php

declare(strict_types=1);

namespace Storm\Projector;

use Storm\Contract\Projector\EmitterProjector;
use Storm\Contract\Projector\ProjectorManagerInterface;
use Storm\Contract\Projector\QueryProjector;
use Storm\Contract\Projector\ReadModel;
use Storm\Contract\Projector\ReadModelProjector;
use Storm\Projector\Connector\ConnectionManager;
use Storm\Projector\Connector\SubscriptionFactoryResolver;
use Storm\Projector\Options\ProjectionOption;
use Storm\Projector\Workflow\DefaultContext;

final readonly class ProjectorManager implements ProjectorManagerInterface
{
    public function __construct(
        private ProjectorServiceManager $manager,
        private SubscriptionFactoryResolver $resolver,
    ) {}

    public function newQueryProjector(array $options = [], ?string $connection = null): QueryProjector
    {
        [$connectionManager, $projectionOptions] = $this->getConnectionWithOptions($connection, $options);

        $querySubscription = $this->resolver
            ->resolve('query', $connectionManager)
            ->create(null, null, $projectionOptions);

        return new ProjectQuery($querySubscription, new DefaultContext());
    }

    public function newEmitterProjector(string $streamName, array $options = [], ?string $connection = null): EmitterProjector
    {
        [$connectionManager, $projectionOptions] = $this->getConnectionWithOptions($connection, $options);

        $emitterSubscription = $this->resolver
            ->resolve('emitter', $connectionManager)
            ->create($streamName, null, $projectionOptions);

        return new ProjectEmitter($emitterSubscription, new DefaultContext(), $streamName);
    }

    public function newReadModelProjector(string $streamName, ReadModel $readModel, array $options = [], ?string $connection = null): ReadModelProjector
    {
        [$connectionManager, $projectionOptions] = $this->getConnectionWithOptions($connection, $options);

        $readModelSubscription = $this->resolver
            ->resolve('read_model', $connectionManager)
            ->create($streamName, $readModel, $projectionOptions);

        return new ProjectReadModel($readModelSubscription, new DefaultContext(), $streamName);
    }

    /**
     * @return array{ConnectionManager, ProjectionOption}
     */
    private function getConnectionWithOptions(?string $connection = null, array $options = []): array
    {
        $connectionManager = $this->manager->connection($connection);

        $options = $connectionManager->toProjectionOption($options);

        return [$connectionManager, $options];
    }
}
